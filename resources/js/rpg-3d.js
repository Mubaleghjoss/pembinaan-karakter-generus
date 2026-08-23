import * as THREE from 'three';

const TILE_SIZE = 4;
const CAMERA_HEIGHT = 1.45;
const PLAYER_RADIUS = 0.42;
const MOVE_SPEED = 4.8;
const TURN_SPEED = Math.PI * 1.6;
const BUTTON_TURN_STEP = Math.PI / 12;
const DAMPING = 12;
const DYNAMIC_VISUAL_LERP = 5.2;
const TOUCH_MOVE_DEADZONE = 0.2;
const TOUCH_MOVE_RANGE = 82;
const TOUCH_TURN_SENSITIVITY = 0.007;
const STATE_SYNC_INTERVAL_MS = 90;
const FOOTSTEP_INTERVAL_MS = 360;
const FPS_SAMPLE_MS = 900;
const PLAYER_SNAP_DISTANCE = TILE_SIZE * 1.65;
const SHOT_SPEED = TILE_SIZE * 14; // kecepatan peluru terbang (unit dunia / detik)
const PLAYER_IDLE_LERP = 14;

const DIRECTIONS = [
    { dx: 0, dy: 1, label: 'Utara' },
    { dx: 1, dy: 0, label: 'Timur' },
    { dx: 0, dy: -1, label: 'Selatan' },
    { dx: -1, dy: 0, label: 'Barat' },
];

const THEME_COLORS = {
    grass: {
        skyTop: 0x8bd8ff,
        skyBottom: 0xdff8e8,
        fog: 0xdff8e8,
        floor: 0x4f9f6f,
        floorAlt: 0x3f8c5d,
        wall: 0x31513d,
        wallCap: 0x223b2c,
        grid: 0xbdf7d3,
        lightSky: 0xf3fff9,
        lightGround: 0x31513d,
    },
    desert: {
        skyTop: 0xf9d08c,
        skyBottom: 0xfff3d0,
        fog: 0xfff3d0,
        floor: 0xd7a751,
        floorAlt: 0xbe8f3c,
        wall: 0x80613b,
        wallCap: 0x5e4428,
        grid: 0xffe3a3,
        lightSky: 0xfff4cf,
        lightGround: 0x8a6238,
    },
    castle: {
        skyTop: 0xa8b7c8,
        skyBottom: 0xdfe6ee,
        fog: 0xdfe6ee,
        floor: 0x7d8793,
        floorAlt: 0x68727d,
        wall: 0x3d4652,
        wallCap: 0x2f3742,
        grid: 0xc6d2df,
        lightSky: 0xf4f7fb,
        lightGround: 0x334155,
    },
    forest: {
        skyTop: 0x1f6f54,
        skyBottom: 0xd8f5e4,
        fog: 0xd8f5e4,
        floor: 0x2f7f56,
        floorAlt: 0x245c41,
        wall: 0x1f3f32,
        wallCap: 0x172f26,
        grid: 0x7edfa4,
        lightSky: 0xe5fff0,
        lightGround: 0x1f3f32,
    },
    snow: {
        skyTop: 0xbfe8ff,
        skyBottom: 0xf4fbff,
        fog: 0xf4fbff,
        floor: 0xd8ebf8,
        floorAlt: 0xbbd9eb,
        wall: 0x7f9db0,
        wallCap: 0x6f8796,
        grid: 0xffffff,
        lightSky: 0xffffff,
        lightGround: 0x7f9db0,
    },
};

class RpgThreeScene {
    constructor(root) {
        this.root = root;
        this.providerName = root.getAttribute('data-rpg-3d-provider') || root.dataset.rpg3dProvider;
        this.controlsName = root.getAttribute('data-rpg-3d-controls') || root.dataset.rpg3dControls;
        this.readOnly = (root.getAttribute('data-rpg-3d-readonly') || root.dataset.rpg3dReadonly) === 'true';
        this.canReset = (root.getAttribute('data-rpg-3d-resettable') || root.dataset.rpg3dResettable) === 'true';
        this.headingIndex = 0;
        this.state = {};
        this.lastMapKey = '';
        this.dynamicObjects = {
            npcs: new Map(),
            enemies: new Map(),
            pickups: new Map(),
            players: new Map(),
        };
        this.cameraTarget = new THREE.Vector3();
        this.cameraLookTarget = new THREE.Vector3(0, CAMERA_HEIGHT, -TILE_SIZE);
        this.playerVisual = new THREE.Vector3(0, CAMERA_HEIGHT, 0);
        this.playerVelocity = new THREE.Vector3();
        this.playerInitialized = false;
        this.currentPlayerCell = null;
        this.lastDispatchedCellKey = '';
        this.playerMotion = {
            moving: false,
            stride: 0,
        };
        this.yaw = 0;
        this.noticeText = '';
        this.noticeUntil = 0;
        this.lastDialogKey = '';
        this.lastStateSyncAt = 0;
        this.uiOpen = false;
        this.uiHidden = false;
        this.controlState = {
            forward: false,
            back: false,
            strafeLeft: false,
            strafeRight: false,
            turnLeft: false,
            turnRight: false,
        };
        this.clock = new THREE.Clock();
        this.animationFrame = null;
        this.lastMinimapKey = '';
        this.skyTexture = null;
        this.touchControls = {
            move: { active: false, pointerId: null, startX: 0, startY: 0, axisX: 0, axisY: 0, preferAxis: 'forward' },
            turn: { active: false, pointerId: null, startX: 0, startY: 0, lastX: 0, lastY: 0 },
        };
        this.pointerLook = { active: false, pointerId: null, lastX: 0 };
        this.lastFootstepAt = 0;
        this.audio = {
            context: null,
            master: null,
            ambient: null,
            noiseBuffer: null,
            unlocked: false,
            failed: false,
        };
        this.prebuilt = {
            pickup: {},
            shotLine: null,
            shotGeometry: null,
            shotMaterial: null,
        };
        const pixelRatio = Math.min(window.devicePixelRatio || 1, 2);
        this.performance = {
            basePixelRatio: pixelRatio,
            currentPixelRatio: Math.min(pixelRatio, 1.75),
            frames: 0,
            lastSampleAt: performance.now(),
        };
        this.pools = {
            pickups: { shield: [], ammo: [] },
        };

        this.mount();
        this.bindControls();
        this.syncState(true);
        this.lastStateSyncAt = performance.now();
        this.animate();
    }

    mount() {
        this.root.classList.add('pkg-rpg-3d-ready');
        this.root.tabIndex = 0;
        this.root.innerHTML = `
            <div class="pkg-rpg-3d-canvas" data-rpg-3d-canvas></div>
            <div class="pkg-rpg-3d-ui-actions">
                ${this.canReset ? '<button type="button" data-rpg-3d-action="reset" title="Reset poin dan jawab ulang NPC">Reset</button>' : ''}
                <button type="button" data-rpg-3d-ui-toggle aria-pressed="false" title="Tampilkan panel game">
                    <span data-rpg-3d-ui-toggle-text>Panel</span>
                </button>
                <button type="button" data-rpg-3d-ui-close title="Sembunyikan panel">X</button>
            </div>
            <div class="pkg-rpg-3d-hud">
                <div class="pkg-rpg-3d-stat pkg-rpg-3d-avatar-stat">
                    <span>Karakter</span>
                    <strong data-rpg-3d-player-avatar>?</strong>
                </div>
                <div class="pkg-rpg-3d-stat">
                    <span>NPC</span>
                    <strong data-rpg-3d-npc>0/0</strong>
                </div>
                <div class="pkg-rpg-3d-stat">
                    <span>Peluru</span>
                    <strong data-rpg-3d-ammo>0</strong>
                </div>
                <div class="pkg-rpg-3d-stat">
                    <span>Tameng</span>
                    <strong data-rpg-3d-shield>OFF</strong>
                </div>
            </div>
            <div class="pkg-rpg-3d-compass">
                <div class="pkg-rpg-3d-compass-ring">
                    <span>U</span>
                    <strong data-rpg-3d-heading>Utara</strong>
                </div>
                <div class="pkg-rpg-3d-target" data-rpg-3d-target>Target: cari NPC</div>
            </div>
            <div class="pkg-rpg-3d-energy" data-rpg-3d-energy-wrap style="display:none;">
                <span class="pkg-rpg-3d-energy-label">⚡ Energi <em data-rpg-3d-energy-text>0/100</em></span>
                <div class="pkg-rpg-3d-energy-bar"><div class="pkg-rpg-3d-energy-fill" data-rpg-3d-energy-fill></div></div>
            </div>
            <div class="pkg-rpg-3d-skills" data-rpg-3d-skills-wrap style="display:none;" aria-label="Skill pemain">
                <button type="button" class="pkg-rpg-3d-skill pkg-rpg-3d-skill--dash" data-rpg-3d-action="dash" title="Lari (tombol Z)">
                    <span class="pkg-rpg-3d-skill-key" aria-hidden="true">Z</span>
                    <span class="pkg-rpg-3d-skill-icon">💨</span>
                    <span class="pkg-rpg-3d-skill-name">Lari</span>
                    <span class="pkg-rpg-3d-skill-cd" data-rpg-3d-skill-cd="dash"></span>
                </button>
                <button type="button" class="pkg-rpg-3d-skill pkg-rpg-3d-skill--ulti" data-rpg-3d-action="ulti" title="Ulti / butuh 60 energi (tombol X)">
                    <span class="pkg-rpg-3d-skill-key" aria-hidden="true">X</span>
                    <span class="pkg-rpg-3d-skill-icon">💥</span>
                    <span class="pkg-rpg-3d-skill-name">Ulti</span>
                    <span class="pkg-rpg-3d-skill-cost" data-rpg-3d-skill-cost="ulti">60</span>
                    <span class="pkg-rpg-3d-skill-cd" data-rpg-3d-skill-cd="ulti"></span>
                </button>
                <button type="button" class="pkg-rpg-3d-skill pkg-rpg-3d-skill--rage" data-rpg-3d-action="rage" title="Rage / butuh 100 energi (tombol C)">
                    <span class="pkg-rpg-3d-skill-key" aria-hidden="true">C</span>
                    <span class="pkg-rpg-3d-skill-icon">🔥</span>
                    <span class="pkg-rpg-3d-skill-name">Rage</span>
                    <span class="pkg-rpg-3d-skill-cost" data-rpg-3d-skill-cost="rage">100</span>
                    <span class="pkg-rpg-3d-skill-cd" data-rpg-3d-skill-cd="rage"></span>
                </button>
            </div>
            <div class="pkg-rpg-3d-minimap" data-rpg-3d-minimap aria-label="Minimap arena"></div>
            <div class="pkg-rpg-3d-dialog" data-rpg-3d-dialog hidden></div>
            <div class="pkg-rpg-3d-touch-hints">
                <span>Swipe kiri: gerak</span>
                <span>Swipe kanan: kamera</span>
            </div>
            <div class="pkg-rpg-3d-controls">
                <button type="button" data-rpg-3d-action="strafe-left" title="Geser kiri">Kiri</button>
                <button type="button" data-rpg-3d-action="forward" title="Maju">Maju</button>
                <button type="button" data-rpg-3d-action="strafe-right" title="Geser kanan">Kanan</button>
                <button type="button" data-rpg-3d-action="back" title="Mundur">Mundur</button>
                <button type="button" data-rpg-3d-action="turn-left" title="Putar kamera kiri">Putar -</button>
                <button type="button" data-rpg-3d-action="turn-right" title="Putar kamera kanan">Putar +</button>
                <button type="button" data-rpg-3d-action="shoot" title="Tembak">Tembak</button>
                <button type="button" data-rpg-3d-action="fullscreen" title="Layar penuh">Layar</button>
                <button type="button" data-rpg-3d-action="view2d" title="Kembali ke tampilan 2D">2D</button>
            </div>
            ${!this.readOnly ? `
            <div class="pkg-rpg-3d-mobile-controls" aria-label="Kontrol 3D mobile">
                <div class="pkg-rpg-3d-mobile-pad pkg-rpg-3d-mobile-pad--move" aria-label="Gerak karakter">
                    <span></span>
                    <button type="button" data-rpg-3d-action="forward" aria-label="Maju"><span aria-hidden="true">&uarr;</span></button>
                    <span></span>
                    <button type="button" data-rpg-3d-action="strafe-left" aria-label="Geser kiri"><span aria-hidden="true">&larr;</span></button>
                    <span class="pkg-rpg-3d-mobile-pad-core" aria-hidden="true"></span>
                    <button type="button" data-rpg-3d-action="strafe-right" aria-label="Geser kanan"><span aria-hidden="true">&rarr;</span></button>
                    <span></span>
                    <button type="button" data-rpg-3d-action="back" aria-label="Mundur"><span aria-hidden="true">&darr;</span></button>
                    <span></span>
                </div>
                <div class="pkg-rpg-3d-mobile-pad pkg-rpg-3d-mobile-pad--turn" aria-label="Putar kamera">
                    <button type="button" data-rpg-3d-action="turn-left" aria-label="Putar kamera kiri"><span aria-hidden="true">&#8630;</span></button>
                    <button type="button" data-rpg-3d-action="turn-right" aria-label="Putar kamera kanan"><span aria-hidden="true">&#8631;</span></button>
                </div>
            </div>
            ` : ''}
            <div class="pkg-rpg-3d-note">W/S maju, A/D geser, Q/E putar, Space tembak. Skill: Z Lari, X Ulti, C Rage.</div>
        `;

        this.canvasHost = this.root.querySelector('[data-rpg-3d-canvas]');
        this.hudPlayerAvatar = this.root.querySelector('[data-rpg-3d-player-avatar]');
        this.hudNpc = this.root.querySelector('[data-rpg-3d-npc]');
        this.hudAmmo = this.root.querySelector('[data-rpg-3d-ammo]');
        this.hudShield = this.root.querySelector('[data-rpg-3d-shield]');
        this.hudEnergyWrap = this.root.querySelector('[data-rpg-3d-energy-wrap]');
        this.hudEnergyFill = this.root.querySelector('[data-rpg-3d-energy-fill]');
        this.hudEnergyText = this.root.querySelector('[data-rpg-3d-energy-text]');
        this.skillsWrap = this.root.querySelector('[data-rpg-3d-skills-wrap]');
        this.skillButtons = {
            dash: this.root.querySelector('.pkg-rpg-3d-skill--dash'),
            ulti: this.root.querySelector('.pkg-rpg-3d-skill--ulti'),
            rage: this.root.querySelector('.pkg-rpg-3d-skill--rage'),
        };
        this.skillCd = {
            dash: this.root.querySelector('[data-rpg-3d-skill-cd="dash"]'),
            ulti: this.root.querySelector('[data-rpg-3d-skill-cd="ulti"]'),
            rage: this.root.querySelector('[data-rpg-3d-skill-cd="rage"]'),
        };
        this.headingLabel = this.root.querySelector('[data-rpg-3d-heading]');
        this.targetLabel = this.root.querySelector('[data-rpg-3d-target]');
        this.minimap = this.root.querySelector('[data-rpg-3d-minimap]');
        this.dialogHost = this.root.querySelector('[data-rpg-3d-dialog]');
        this.uiToggle = this.root.querySelector('[data-rpg-3d-ui-toggle]');
        this.uiToggleText = this.root.querySelector('[data-rpg-3d-ui-toggle-text]');
        this.uiClose = this.root.querySelector('[data-rpg-3d-ui-close]');

        this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false, powerPreference: 'high-performance' });
        this.renderer.setPixelRatio(this.performance.currentPixelRatio);
        this.renderer.setClearColor(0xe7f8ed, 1);
        this.renderer.domElement.className = 'pkg-rpg-3d-renderer';
        this.renderer.domElement.style.touchAction = 'none';
        this.canvasHost.appendChild(this.renderer.domElement);

        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(68, 1, 0.1, 220);
        this.camera.position.set(0, CAMERA_HEIGHT, 0);

        this.ambientLight = new THREE.HemisphereLight(0xffffff, 0x314155, 1.35);
        this.scene.add(this.ambientLight);

        this.keyLight = new THREE.DirectionalLight(0xffffff, 1.1);
        this.keyLight.position.set(8, 12, 8);
        this.scene.add(this.keyLight);

        this.fillLight = new THREE.DirectionalLight(0xffffff, 0.75);
        this.fillLight.position.set(-8, 7, -10);
        this.scene.add(this.fillLight);

        const playerLight = new THREE.PointLight(0xffffff, 1.15, TILE_SIZE * 4);
        this.camera.add(playerLight);
        this.playerViewModel = this.makeFirstPersonHands();
        this.camera.add(this.playerViewModel);
        this.scene.add(this.camera);

        this.staticGroup = new THREE.Group();
        this.scene.add(this.staticGroup);

        this.npcGroup = new THREE.Group();
        this.enemyGroup = new THREE.Group();
        this.bossGroup = new THREE.Group();
        this.pickupGroup = new THREE.Group();
        this.playerGroup = new THREE.Group();
        this.scene.add(this.npcGroup, this.enemyGroup, this.bossGroup, this.pickupGroup, this.playerGroup);

        this.shieldAura = this.makeShieldAura();
        this.scene.add(this.shieldAura);
        this.prewarmRuntimeAssets();

        if (typeof ResizeObserver !== 'undefined') {
            this.resizeObserver = new ResizeObserver(() => this.resize());
            this.resizeObserver.observe(this.root);
        }
        window.addEventListener('resize', () => this.resize());
        this.updateUiState();
        this.resize();
    }

    bindControls() {
        const prepareAudio = () => this.unlockAudio();
        this.root.addEventListener('pointerdown', prepareAudio, { capture: true });
        this.root.addEventListener('click', prepareAudio);
        this.root.addEventListener('keydown', prepareAudio);
        this.root.addEventListener('click', () => this.root.focus({ preventScroll: true }));

        this.uiToggle?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (this.uiHidden) {
                this.uiHidden = false;
                this.uiOpen = true;
            } else {
                this.uiOpen = !this.uiOpen;
            }

            this.updateUiState();
            this.root.focus({ preventScroll: true });
        });

        this.uiClose?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            this.uiHidden = true;
            this.uiOpen = false;
            this.stopButtonAction('forward');
            this.stopButtonAction('back');
            this.stopButtonAction('strafe-left');
            this.stopButtonAction('strafe-right');
            this.stopButtonAction('turn-left');
            this.stopButtonAction('turn-right');
            this.stopTouchControl();
            this.updateUiState();
            this.root.focus({ preventScroll: true });
        });

        this.root.querySelectorAll('[data-rpg-3d-action]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
            });
            button.addEventListener('pointerdown', (event) => {
                event.preventDefault();
                event.stopPropagation();
                this.root.focus({ preventScroll: true });
                try {
                    button.setPointerCapture(event.pointerId);
                } catch (error) {
                    // ignore unsupported capture
                }
                this.startButtonAction(button.getAttribute('data-rpg-3d-action'));
            });
            button.addEventListener('pointerup', (event) => {
                event.preventDefault();
                this.stopButtonAction(button.getAttribute('data-rpg-3d-action'));
            });
            button.addEventListener('pointerleave', () => this.stopButtonAction(button.getAttribute('data-rpg-3d-action')));
            button.addEventListener('pointercancel', () => this.stopButtonAction(button.getAttribute('data-rpg-3d-action')));
        });

        this.dialogHost?.addEventListener('click', (event) => {
            const answerButton = event.target.closest('[data-rpg-3d-answer]');
            const closeButton = event.target.closest('[data-rpg-3d-close-dialog]');
            const completionCloseButton = event.target.closest('[data-rpg-3d-close-completion]');
            const mapListButton = event.target.closest('[data-rpg-3d-map-list]');
            const resetButton = event.target.closest('[data-rpg-3d-reset]');

            if (!answerButton && !closeButton && !completionCloseButton && !mapListButton && !resetButton) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            if (answerButton) {
                this.invokeControl('answer', { index: Number(answerButton.getAttribute('data-rpg-3d-answer')) });
            } else if (closeButton) {
                this.invokeControl('closeNpc');
                this.root.focus({ preventScroll: true });
            } else if (completionCloseButton) {
                this.invokeControl('closeCompletion');
                this.root.focus({ preventScroll: true });
            } else if (mapListButton) {
                const url = this.state.mapListUrl || mapListButton.getAttribute('data-rpg-3d-map-list');
                if (url) {
                    window.location.href = url;
                }
            } else if (resetButton) {
                this.invokeControl('reset');
            }
        });

        this.root.addEventListener('pointerdown', (event) => this.handlePointerDown(event));
        this.root.addEventListener('pointermove', (event) => this.handlePointerMove(event));
        this.root.addEventListener('pointerup', (event) => this.handlePointerEnd(event));
        this.root.addEventListener('pointercancel', (event) => this.handlePointerEnd(event));
        this.root.addEventListener('lostpointercapture', (event) => this.handlePointerEnd(event));

        this.canvasHost?.addEventListener('pointerdown', (event) => this.handleLookPointerDown(event));
        this.canvasHost?.addEventListener('pointermove', (event) => this.handleLookPointerMove(event));
        this.canvasHost?.addEventListener('pointerup', (event) => this.handleLookPointerEnd(event));
        this.canvasHost?.addEventListener('pointercancel', (event) => this.handleLookPointerEnd(event));
        this.canvasHost?.addEventListener('lostpointercapture', (event) => this.handleLookPointerEnd(event));

        const handleKeyDown = (event) => {
            if (event.__pkgRpg3dHandled) {
                return;
            }
            if (event.target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target.tagName)) {
                return;
            }
            event.__pkgRpg3dHandled = true;

            const key = event.key.toLowerCase();
            if (['w', 'arrowup'].includes(key)) {
                event.preventDefault();
                event.stopPropagation();
                this.controlState.forward = true;
            } else if (['s', 'arrowdown'].includes(key)) {
                event.preventDefault();
                event.stopPropagation();
                this.controlState.back = true;
            } else if (key === 'a') {
                event.preventDefault();
                event.stopPropagation();
                this.controlState.strafeLeft = true;
            } else if (key === 'd') {
                event.preventDefault();
                event.stopPropagation();
                this.controlState.strafeRight = true;
            } else if (['q', 'arrowleft'].includes(key)) {
                event.preventDefault();
                event.stopPropagation();
                this.controlState.turnLeft = true;
            } else if (['e', 'arrowright'].includes(key)) {
                event.preventDefault();
                event.stopPropagation();
                this.controlState.turnRight = true;
            } else if (event.code === 'Space' || key === 'enter') {
                event.preventDefault();
                event.stopPropagation();
                this.performAction('shoot');
            } else if (key === 'z') {
                event.preventDefault();
                event.stopPropagation();
                this.performAction('dash');
            } else if (key === 'x') {
                event.preventDefault();
                event.stopPropagation();
                this.performAction('ulti');
            } else if (key === 'c') {
                event.preventDefault();
                event.stopPropagation();
                this.performAction('rage');
            }
        };

        const handleKeyUp = (event) => {
            if (event.__pkgRpg3dHandled) {
                return;
            }
            event.__pkgRpg3dHandled = true;
            const key = event.key.toLowerCase();
            if (['w', 'arrowup'].includes(key)) {
                event.stopPropagation();
                this.controlState.forward = false;
            } else if (['s', 'arrowdown'].includes(key)) {
                event.stopPropagation();
                this.controlState.back = false;
            } else if (key === 'a') {
                event.stopPropagation();
                this.controlState.strafeLeft = false;
            } else if (key === 'd') {
                event.stopPropagation();
                this.controlState.strafeRight = false;
            } else if (['q', 'arrowleft'].includes(key)) {
                event.stopPropagation();
                this.controlState.turnLeft = false;
            } else if (['e', 'arrowright'].includes(key)) {
                event.stopPropagation();
                this.controlState.turnRight = false;
            }
        };

        this.root.addEventListener('keydown', handleKeyDown);
        this.root.addEventListener('keyup', handleKeyUp);
        window.addEventListener('keydown', handleKeyDown);
        window.addEventListener('keyup', handleKeyUp);
    }

    performAction(action) {
        if (this.isDialogOpen() && !['view2d', 'fullscreen', 'reset'].includes(action)) {
            return;
        }

        if (action === 'reset') {
            if (!this.canReset) {
                return;
            }
            this.invokeControl('reset');
            return;
        }

        if (action === 'turn-left') {
            this.yaw -= BUTTON_TURN_STEP;
            this.syncHeadingFromYaw();
            this.updateDirectionHud();
            return;
        }

        if (action === 'turn-right') {
            this.yaw += BUTTON_TURN_STEP;
            this.syncHeadingFromYaw();
            this.updateDirectionHud();
            return;
        }

        if (action === 'view2d') {
            if (!this.invokeControl('view2d')) {
                this.root.dispatchEvent(new CustomEvent('rpg3d:view2d', { bubbles: true }));
            }
            return;
        }

        if (action === 'fullscreen') {
            this.enterImmersiveMode();
            return;
        }

        // Skill pemain saat lawan bos (delegasi ke Alpine controls).
        if (action === 'dash') { this.invokeControl('dash'); this.lastStateSyncAt = 0; return; }
        if (action === 'ulti') { this.invokeControl('ulti'); this.lastStateSyncAt = 0; return; }
        if (action === 'rage') { this.invokeControl('rage'); this.lastStateSyncAt = 0; return; }

        if (this.readOnly) {
            return;
        }

        if (['forward', 'back', 'strafe-left', 'strafe-right'].includes(action)) {
            return;
        }

        const direction = this.cardinalFromYaw();
        if (action === 'shoot') {
            // Saat lawan bos peluru tak terbatas; selain itu perlu amunisi.
            const canFire = !!this.state.boss || Number(this.state.ammo || 0) > 0;
            this.dispatchShoot(direction.dx, direction.dy);
            if (canFire) {
                this.playSound('shot');
                this.kickWeapon();
                this.flashShot(direction.dx, direction.dy);
            } else {
                this.playSound('miss');
            }
        }
    }

    tryMoveBy(dx, dy) {
        if (!this.canMoveBy(dx, dy)) {
            this.showNotice('Arah tertutup. Belok atau mundur dulu.');
            return false;
        }

        this.dispatchMove(dx, dy);
        return true;
    }

    canMoveBy(dx, dy) {
        const session = this.state.session;
        if (!session) {
            return true;
        }

        const gridSize = Number(this.state.map?.grid_size || this.state.gridSize || 10);
        const newX = Number(session.pos_x || 0) + dx;
        const newY = Number(session.pos_y || 0) + dy;

        if (newX < 0 || newX >= gridSize || newY < 0 || newY >= gridSize) {
            return false;
        }

        return !(this.state.obstacles || []).some((obstacle) => Number(obstacle.x) === newX && Number(obstacle.y) === newY);
    }

    showNotice(message) {
        this.noticeText = message;
        this.noticeUntil = Date.now() + 1400;
        if (this.targetLabel) {
            this.targetLabel.textContent = message;
        }
    }

    updateUiState() {
        this.root.classList.toggle('is-ui-open', this.uiOpen && !this.uiHidden);
        this.root.classList.toggle('is-ui-minimized', !this.uiOpen && !this.uiHidden);
        this.root.classList.toggle('is-ui-hidden', this.uiHidden);

        if (this.uiToggle) {
            this.uiToggle.setAttribute('aria-pressed', this.uiOpen && !this.uiHidden ? 'true' : 'false');
        }

        if (this.uiToggleText) {
            this.uiToggleText.textContent = this.uiHidden
                ? 'Panel'
                : (this.uiOpen ? 'Ringkas' : 'Panel');
        }
    }

    minimizeUi() {
        this.uiOpen = false;
        this.uiHidden = false;
        this.stopButtonAction('forward');
        this.stopButtonAction('back');
        this.stopButtonAction('strafe-left');
        this.stopButtonAction('strafe-right');
        this.stopButtonAction('turn-left');
        this.stopButtonAction('turn-right');
        this.stopTouchControl();
        this.updateUiState();
    }

    startButtonAction(action) {
        if (action === 'forward') {
            this.controlState.forward = true;
        } else if (action === 'back') {
            this.controlState.back = true;
        } else if (action === 'strafe-left') {
            this.controlState.strafeLeft = true;
        } else if (action === 'strafe-right') {
            this.controlState.strafeRight = true;
        } else if (action === 'turn-left') {
            this.controlState.turnLeft = true;
        } else if (action === 'turn-right') {
            this.controlState.turnRight = true;
        } else {
            this.performAction(action);
        }
    }

    stopButtonAction(action) {
        if (action === 'forward') {
            this.controlState.forward = false;
        } else if (action === 'back') {
            this.controlState.back = false;
        } else if (action === 'strafe-left') {
            this.controlState.strafeLeft = false;
        } else if (action === 'strafe-right') {
            this.controlState.strafeRight = false;
        } else if (action === 'turn-left') {
            this.controlState.turnLeft = false;
        } else if (action === 'turn-right') {
            this.controlState.turnRight = false;
        }
    }

    dispatchMove(dx, dy) {
        if (!this.invokeControl('move', { dx, dy })) {
            this.root.dispatchEvent(new CustomEvent('rpg3d:move', {
                bubbles: true,
                detail: { dx, dy },
            }));
        }
        this.playFootstepIfNeeded();
        this.lastStateSyncAt = 0;
    }

    dispatchShoot(dx, dy) {
        if (!this.invokeControl('shoot', { dx, dy })) {
            this.root.dispatchEvent(new CustomEvent('rpg3d:shoot', {
                bubbles: true,
                detail: { dx, dy },
            }));
        }
        this.lastStateSyncAt = 0;
    }

    invokeControl(method, detail = {}) {
        const controls = this.controlsName ? window[this.controlsName] : null;
        if (controls && typeof controls[method] === 'function') {
            try {
                controls[method](detail);
                return true;
            } catch (error) {
                console.error(`RPG 3D ${method} control failed`, error);
            }
        }

        return this.invokeAlpineControl(method, detail);
    }

    invokeAlpineControl(method, detail = {}) {
        const data = this.getAlpineData();
        if (!data) {
            return false;
        }

        const dx = Number(detail.dx || 0);
        const dy = Number(detail.dy || 0);

        try {
            if (method === 'move' && typeof data.movePlayer === 'function') {
                data.movePlayer(dx, dy);
                return true;
            }

            if (method === 'shoot' && typeof data.shootDirection === 'function') {
                data.shootDirection(dx, dy);
                return true;
            }

            if (method === 'view2d' && typeof data.setViewMode === 'function') {
                data.setViewMode('2d');
                return true;
            }

            if (method === 'answer' && typeof data.submitAnswer === 'function') {
                data.submitAnswer(Number(detail.index || 0));
                return true;
            }

            if (method === 'closeNpc' && typeof data.closeDialog === 'function') {
                data.closeDialog();
                return true;
            }

            if (method === 'closeCompletion' && typeof data.closeCompletion === 'function') {
                data.closeCompletion();
                return true;
            }

            if (method === 'reset' && this.canReset && typeof data.resetGame === 'function') {
                data.resetGame();
                return true;
            }
        } catch (error) {
            console.error(`RPG 3D Alpine ${method} fallback failed`, error);
        }

        return false;
    }

    enterImmersiveMode() {
        if (this.readOnly) {
            return;
        }

        const request = !document.fullscreenElement && this.root.requestFullscreen
            ? this.root.requestFullscreen({ navigationUI: 'hide' }).catch(() => null)
            : Promise.resolve();

        request.then(() => {
            if (screen.orientation && typeof screen.orientation.lock === 'function') {
                screen.orientation.lock('landscape').catch(() => null);
            }
        });

        this.root.focus({ preventScroll: true });
    }

    handlePointerDown(event) {
        if (this.readOnly || this.isDialogOpen() || event.pointerType === 'mouse' || event.target.closest('[data-rpg-3d-action], [data-rpg-3d-dialog], .pkg-rpg-3d-ui-actions')) {
            return;
        }

        event.preventDefault();
        const rect = this.root.getBoundingClientRect();
        const side = event.clientX - rect.left < rect.width / 2 ? 'move' : 'turn';
        const control = this.touchControls[side];

        if (control.active) {
            return;
        }

        control.active = true;
        control.pointerId = event.pointerId;
        control.startX = event.clientX;
        control.startY = event.clientY;
        if (side === 'move') {
            control.axisX = 0;
            control.axisY = 0;
        } else {
            control.lastX = event.clientX;
            control.lastY = event.clientY;
        }
        this.root.classList.add(side === 'move' ? 'is-touch-moving' : 'is-touch-turning');

        try {
            this.root.setPointerCapture(event.pointerId);
        } catch (error) {
            // ignore unsupported capture
        }

        this.processTouchControl(event, side);
    }

    handleLookPointerDown(event) {
        if (this.readOnly || this.isDialogOpen() || event.pointerType !== 'mouse' || event.button !== 0 || event.target.closest('[data-rpg-3d-action], [data-rpg-3d-dialog], .pkg-rpg-3d-ui-actions')) {
            return;
        }

        event.preventDefault();
        this.pointerLook.active = true;
        this.pointerLook.pointerId = event.pointerId;
        this.pointerLook.lastX = event.clientX;
        this.root.focus({ preventScroll: true });

        try {
            this.canvasHost.setPointerCapture(event.pointerId);
        } catch (error) {
            // ignore unsupported capture
        }
    }

    handleLookPointerMove(event) {
        if (!this.pointerLook.active || this.pointerLook.pointerId !== event.pointerId) {
            return;
        }

        event.preventDefault();
        const movementX = event.clientX - this.pointerLook.lastX;
        this.pointerLook.lastX = event.clientX;
        this.yaw += movementX * 0.004;
        this.syncHeadingFromYaw();
        this.updateDirectionHud();
    }

    handleLookPointerEnd(event) {
        if (this.pointerLook.pointerId !== event.pointerId) {
            return;
        }

        this.pointerLook.active = false;
        this.pointerLook.pointerId = null;
    }

    handlePointerMove(event) {
        const side = this.findTouchControlSide(event.pointerId);
        if (!side) {
            return;
        }

        event.preventDefault();
        this.processTouchControl(event, side);
    }

    handlePointerEnd(event) {
        const side = this.findTouchControlSide(event.pointerId);
        if (side) {
            this.stopTouchControl(side);
        }
    }

    findTouchControlSide(pointerId) {
        if (this.touchControls.move.pointerId === pointerId) {
            return 'move';
        }

        if (this.touchControls.turn.pointerId === pointerId) {
            return 'turn';
        }

        return null;
    }

    processTouchControl(event, side) {
        const control = this.touchControls[side];
        if (!control?.active) {
            return;
        }

        const dx = event.clientX - control.startX;
        const dy = event.clientY - control.startY;

        if (side === 'move') {
            const axisX = clamp(dx / TOUCH_MOVE_RANGE, -1, 1);
            const axisY = clamp(dy / TOUCH_MOVE_RANGE, -1, 1);
            const length = Math.hypot(axisX, axisY);

            if (length <= TOUCH_MOVE_DEADZONE) {
                control.axisX = 0;
                control.axisY = 0;
                this.clearTouchControlState('move');
                return;
            }

            const normalized = length > 1 ? 1 / length : 1;
            control.axisX = axisX * normalized;
            control.axisY = axisY * normalized;

            this.controlState.forward = control.axisY < -TOUCH_MOVE_DEADZONE;
            this.controlState.back = control.axisY > TOUCH_MOVE_DEADZONE;
            this.controlState.strafeLeft = control.axisX < -TOUCH_MOVE_DEADZONE;
            this.controlState.strafeRight = control.axisX > TOUCH_MOVE_DEADZONE;
            return;
        }

        const movementX = event.clientX - control.lastX;
        control.lastX = event.clientX;
        control.lastY = event.clientY;

        if (Number.isFinite(movementX) && movementX !== 0) {
            this.yaw += movementX * TOUCH_TURN_SENSITIVITY;
            this.syncHeadingFromYaw();
            this.updateDirectionHud();
        }
    }

    stopTouchControl(side) {
        const sides = side ? [side] : ['move', 'turn'];

        sides.forEach((name) => {
            const control = this.touchControls[name];
            control.active = false;
            control.pointerId = null;
            if (name === 'move') {
                control.axisX = 0;
                control.axisY = 0;
            }
            this.root.classList.remove(name === 'move' ? 'is-touch-moving' : 'is-touch-turning');
            this.clearTouchControlState(name);
        });
    }

    clearTouchControlState(side = null) {
        if (!side || side === 'move') {
            this.controlState.forward = false;
            this.controlState.back = false;
            this.controlState.strafeLeft = false;
            this.controlState.strafeRight = false;
        }

        if (!side || side === 'turn') {
            this.controlState.turnLeft = false;
            this.controlState.turnRight = false;
        }
    }

    readState() {
        const provider = this.providerName ? window[this.providerName] : null;
        if (typeof provider !== 'function') {
            return this.readAlpineState();
        }

        try {
            const state = provider() || {};
            const fallback = this.readAlpineState(state);
            if ((state.npcs || []).length === 0 && (fallback.npcs || []).length > 0) {
                return fallback;
            }

            return this.hasGameplayState(state) ? state : fallback;
        } catch (error) {
            console.error('RPG 3D state provider failed', error);
            return this.readAlpineState();
        }
    }

    hasGameplayState(state) {
        return !!(
            state
            && (
                state.session
                || (Array.isArray(state.npcs) && state.npcs.length > 0)
                || (Array.isArray(state.obstacles) && state.obstacles.length > 0)
                || (Array.isArray(state.enemies) && state.enemies.length > 0)
            )
        );
    }

    getAlpineData() {
        if (!window.Alpine || typeof window.Alpine.$data !== 'function') {
            return null;
        }

        const host = this.root.closest('[x-data]');
        if (!host) {
            return null;
        }

        try {
            return window.Alpine.$data(host);
        } catch (error) {
            return null;
        }
    }

    readAlpineState(fallback = {}) {
        const data = this.getAlpineData();
        if (data && typeof data.getThreeState === 'function') {
            try {
                const state = data.getThreeState() || {};
                return this.hasGameplayState(state) ? state : fallback;
            } catch (error) {
                console.error('RPG 3D Alpine state fallback failed', error);
            }
        }

        return fallback || {};
    }

    syncState(force = false) {
        const previousAmmo = Number(this.state.ammo || 0);
        const previousShieldActive = !!this.state.shieldActive;
        this.state = this.readState();
        const map = this.state.map || {};
        const gridSize = Number(map.grid_size || this.state.gridSize || 10);
        const theme = map.background_theme || this.state.backgroundTheme || 'grass';
        const mapKey = `${gridSize}:${theme}:${JSON.stringify(this.state.obstacles || [])}`;
        const mapChanged = force || mapKey !== this.lastMapKey;

        if (mapChanged) {
            this.lastMapKey = mapKey;
            this.buildStaticScene(gridSize, theme);
        }

        this.updateHud();
        this.updateDialog();
        this.updateCameraTarget();
        this.syncMarkers();
        if (mapChanged) {
            this.compileRuntimeAssets();
        }
        if (!force) {
            this.playStateAudio(previousAmmo, previousShieldActive);
        }
    }

    buildStaticScene(gridSize, themeName) {
        this.clearGroup(this.staticGroup);

        const colors = THEME_COLORS[themeName] || THEME_COLORS.grass;
        this.applyThemeEnvironment(colors, gridSize);

        const center = (gridSize - 1) / 2;
        const floorMaterialA = new THREE.MeshLambertMaterial({ color: colors.floor, emissive: colors.floor, emissiveIntensity: 0.08 });
        const floorMaterialB = new THREE.MeshLambertMaterial({ color: colors.floorAlt, emissive: colors.floorAlt, emissiveIntensity: 0.08 });
        const floorGeometry = new THREE.BoxGeometry(TILE_SIZE, 0.08, TILE_SIZE);

        for (let y = 0; y < gridSize; y += 1) {
            for (let x = 0; x < gridSize; x += 1) {
                const floor = new THREE.Mesh(floorGeometry, (x + y) % 2 === 0 ? floorMaterialA : floorMaterialB);
                floor.position.set((x - center) * TILE_SIZE, -0.04, (center - y) * TILE_SIZE);
                this.staticGroup.add(floor);
            }
        }

        const wallMaterial = new THREE.MeshLambertMaterial({ color: colors.wall, emissive: colors.wall, emissiveIntensity: 0.04 });
        const wallGeometry = new THREE.BoxGeometry(TILE_SIZE, TILE_SIZE * 1.1, TILE_SIZE);
        const wallCapGeometry = new THREE.BoxGeometry(TILE_SIZE + 0.08, 0.08, TILE_SIZE + 0.08);
        const wallEdgeGeometry = new THREE.EdgesGeometry(wallGeometry);
        const wallCapMaterial = new THREE.MeshLambertMaterial({ color: colors.wallCap || darkenHexColor(colors.wall, 0.72) });
        const wallEdgeMaterial = new THREE.LineBasicMaterial({ color: 0x111827, transparent: true, opacity: 0.82 });

        const addWall = (x, y, heightScale = 1) => {
            const wall = new THREE.Mesh(wallGeometry, wallMaterial);
            wall.position.set((x - center) * TILE_SIZE, TILE_SIZE * 0.55 * heightScale, (center - y) * TILE_SIZE);
            wall.scale.y = heightScale;
            const edge = new THREE.LineSegments(wallEdgeGeometry, wallEdgeMaterial);
            edge.position.copy(wall.position);
            edge.scale.y = heightScale;
            const cap = new THREE.Mesh(wallCapGeometry, wallCapMaterial);
            cap.position.set(wall.position.x, (TILE_SIZE * 1.1 * heightScale) + 0.03, wall.position.z);
            this.staticGroup.add(wall, edge, cap);
        };

        (this.state.obstacles || []).forEach((obstacle) => addWall(Number(obstacle.x), Number(obstacle.y), 1));

        for (let i = 0; i < gridSize; i += 1) {
            addWall(i, -1, 0.75);
            addWall(i, gridSize, 0.75);
            addWall(-1, i, 0.75);
            addWall(gridSize, i, 0.75);
        }

        const grid = new THREE.GridHelper(gridSize * TILE_SIZE, gridSize, colors.grid || 0xffffff, darkenHexColor(colors.grid || 0xffffff, 0.5));
        grid.position.y = 0.03;
        this.staticGroup.add(grid);
        this.updateMinimap();
    }

    applyThemeEnvironment(colors, gridSize) {
        const skyTexture = this.makeSkyTexture(colors);
        if (this.skyTexture) {
            this.skyTexture.dispose();
        }
        this.skyTexture = skyTexture;
        this.scene.background = skyTexture;
        this.renderer.setClearColor(colors.fog, 1);
        this.scene.fog = new THREE.Fog(colors.fog, TILE_SIZE * 7, TILE_SIZE * Math.max(14, gridSize * 1.8));

        if (this.ambientLight) {
            this.ambientLight.color.setHex(colors.lightSky || 0xffffff);
            this.ambientLight.groundColor.setHex(colors.lightGround || colors.wall || 0x314155);
            this.ambientLight.intensity = 1.35;
        }

        if (this.keyLight) {
            this.keyLight.color.setHex(colors.lightSky || 0xffffff);
            this.keyLight.intensity = 1.08;
        }

        if (this.fillLight) {
            this.fillLight.color.setHex(colors.skyBottom || colors.fog || 0xffffff);
            this.fillLight.intensity = 0.68;
        }
    }

    makeSkyTexture(colors) {
        const canvas = document.createElement('canvas');
        canvas.width = 32;
        canvas.height = 256;
        const context = canvas.getContext('2d');
        const gradient = context.createLinearGradient(0, 0, 0, canvas.height);
        gradient.addColorStop(0, hexColorToCss(colors.skyTop || colors.fog));
        gradient.addColorStop(0.72, hexColorToCss(colors.skyBottom || colors.fog));
        gradient.addColorStop(1, hexColorToCss(colors.fog || colors.skyBottom || 0xffffff));
        context.fillStyle = gradient;
        context.fillRect(0, 0, canvas.width, canvas.height);

        const texture = new THREE.CanvasTexture(canvas);
        texture.colorSpace = THREE.SRGBColorSpace;
        texture.magFilter = THREE.LinearFilter;
        texture.minFilter = THREE.LinearFilter;
        texture.needsUpdate = true;
        return texture;
    }

    syncMarkers() {
        const answered = new Set((this.state.session?.answered_npcs || []).map((id) => Number(id)));
        const npcs = (this.state.npcs || []).filter((npc) => npc.is_active !== false);
        const enemies = this.state.enemies || [];
        const onlinePlayers = this.state.onlinePlayers || this.state.online_players || [];
        const pickups = [
            ...(this.state.pickups?.shield || []).map((pickup) => ({ ...pickup, type: 'shield' })),
            ...(this.state.pickups?.ammo || []).map((pickup) => ({ ...pickup, type: 'ammo' })),
        ];

        this.syncCollection(this.dynamicObjects.npcs, this.npcGroup, npcs, (npc) => `npc-${npc.id}-${npc.avatar_display || npc.avatar || 'npc'}`, (npc) => {
            const group = this.makeNpcMarker(npc);
            group.userData.labelText = npc.nama || 'NPC';
            return group;
        }, (object, npc) => {
            const pos = this.tileToWorld(Number(npc.pos_x), Number(npc.pos_y));
            object.position.set(pos.x, 0, pos.z);
            object.visible = true;
            object.traverse((child) => {
                if (child.material) {
                    child.material.opacity = answered.has(Number(npc.id)) ? 0.38 : 1;
                    child.material.transparent = answered.has(Number(npc.id));
                }
            });
        });

        this.syncCollection(this.dynamicObjects.enemies, this.enemyGroup, enemies, (enemy, index) => `enemy-${enemy.id || index}-${enemy.avatar || 'enemy'}`, (enemy) => this.makeEnemyMarker(enemy), (object, enemy) => {
            const pos = this.tileToWorld(Number(enemy.x), Number(enemy.y));
            this.setDynamicTarget(object, pos, 620);
            object.visible = true;
        });

        this.syncCollection(this.dynamicObjects.players, this.playerGroup, onlinePlayers, (player, index) => `player-${player.siswa_id || player.id || index}-${player.avatar_display || player.avatar || 'player'}`, (player) => this.makeOtherPlayerMarker(player), (object, player) => {
            const pos = this.tileToWorld(Number(player.pos_x), Number(player.pos_y));
            this.setDynamicTarget(object, pos, 620);
            object.visible = true;
        });

        this.syncCollection(this.dynamicObjects.pickups, this.pickupGroup, pickups, (pickup) => `${pickup.type}-${pickup.x}-${pickup.y}`, (pickup) => this.acquirePickupObject(pickup.type), (object, pickup) => {
            const pos = this.tileToWorld(Number(pickup.x), Number(pickup.y));
            object.position.set(pos.x, 0, pos.z);
            object.visible = true;
        }, (object) => this.releasePickupObject(object));

        const player = this.state.session || { pos_x: 0, pos_y: 0 };
        const playerPos = this.tileToWorld(Number(player.pos_x || 0), Number(player.pos_y || 0));
        this.shieldAura.position.set(playerPos.x, CAMERA_HEIGHT * 0.55, playerPos.z);
        this.shieldAura.visible = !!this.state.shieldActive;
        this.updateBoss();
        this.updateBossExtras();
        this.updateDirectionHud();
        this.updateMinimap();
    }

    updateBossExtras() {
        if (!this.dynamicObjects.minions) this.dynamicObjects.minions = new Map();
        if (!this.dynamicObjects.projectiles) this.dynamicObjects.projectiles = new Map();
        if (!this.dynamicObjects.drops) this.dynamicObjects.drops = new Map();

        const minions = this.state.minions || [];
        const projectiles = this.state.bossProjectiles || [];
        const drops = [
            ...(this.state.healthDrops || []).map((d) => ({ ...d, type: 'health' })),
            ...(this.state.energyDrops || []).map((d) => ({ ...d, type: 'energy' })),
        ];

        // Minion (kotak ungu kecil, mengejar).
        this.syncCollection(this.dynamicObjects.minions, this.bossGroup, minions,
            (m, i) => `minion-${i}-${m.x}-${m.y}`,
            () => this.makeMinionMarker(),
            (object, m) => {
                const pos = this.tileToWorld(Number(m.x), Number(m.y));
                this.setDynamicTarget(object, pos, 500);
                object.visible = true;
            });

        // Proyektil bos (bola merah menyala).
        this.syncCollection(this.dynamicObjects.projectiles, this.bossGroup, projectiles,
            (p, i) => `proj-${i}-${p.x}-${p.y}`,
            () => this.makeProjectileMarker(),
            (object, p) => {
                const pos = this.tileToWorld(Number(p.x), Number(p.y));
                object.position.set(pos.x, CAMERA_HEIGHT * 0.5, pos.z);
                object.visible = true;
            });

        // Drop darah/energi.
        this.syncCollection(this.dynamicObjects.drops, this.pickupGroup, drops,
            (d) => `drop-${d.type}-${d.x}-${d.y}`,
            (d) => this.makeDropMarker(d.type),
            (object, d) => {
                const pos = this.tileToWorld(Number(d.x), Number(d.y));
                object.position.set(pos.x, 0.4, pos.z);
                object.visible = true;
            });

        // HUD energi di layar (jika ada bos).
        if (this.hudEnergyWrap) {
            const show = !!this.state.boss;
            this.hudEnergyWrap.style.display = show ? '' : 'none';
            if (this.skillsWrap) this.skillsWrap.style.display = show ? '' : 'none';
            if (show && this.hudEnergyFill) {
                const pct = Number(this.state.energyMax) > 0 ? clamp(Number(this.state.energy) / Number(this.state.energyMax), 0, 1) : 0;
                this.hudEnergyFill.style.width = Math.round(pct * 100) + '%';
                if (this.hudEnergyText) this.hudEnergyText.textContent = `${Number(this.state.energy) || 0}/${Number(this.state.energyMax) || 100}`;
            }
            if (show) this.updateSkillButtons();
        }
    }

    updateSkillButtons() {
        const sk = this.state.skills || {};
        // Dash
        if (this.skillButtons.dash) {
            const ready = !!sk.dashReady || !!sk.rageActive;
            this.skillButtons.dash.classList.toggle('is-ready', ready);
            this.skillButtons.dash.classList.toggle('is-cooldown', !ready);
            this.skillButtons.dash.disabled = !ready;
            if (this.skillCd.dash) this.skillCd.dash.textContent = (!ready && sk.dashCd) ? `${sk.dashCd}s` : '';
        }
        // Ulti
        if (this.skillButtons.ulti) {
            const ready = !!sk.ultiReady;
            this.skillButtons.ulti.classList.toggle('is-ready', ready);
            this.skillButtons.ulti.classList.toggle('is-cooldown', !ready);
            this.skillButtons.ulti.disabled = !ready;
            if (this.skillCd.ulti) this.skillCd.ulti.textContent = (!ready && sk.ultiCd) ? `${sk.ultiCd}s` : '';
        }
        // Rage
        if (this.skillButtons.rage) {
            const ready = !!sk.rageReady && !sk.rageActive;
            this.skillButtons.rage.classList.toggle('is-ready', ready);
            this.skillButtons.rage.classList.toggle('is-active', !!sk.rageActive);
            this.skillButtons.rage.classList.toggle('is-cooldown', !ready && !sk.rageActive);
            this.skillButtons.rage.disabled = !ready && !sk.rageActive;
            if (this.skillCd.rage) this.skillCd.rage.textContent = sk.rageActive ? `${sk.rageLeft}s` : '';
        }
    }

    makeProjectileMarker() {
        const geo = new THREE.SphereGeometry(0.3, 16, 12);
        const mat = new THREE.MeshBasicMaterial({ color: 0xef4444 });
        const mesh = new THREE.Mesh(geo, mat);
        const glow = new THREE.PointLight(0xff5555, 1.4, 6);
        mesh.add(glow);
        return mesh;
    }

    makeDropMarker(type) {
        const group = new THREE.Group();
        const color = type === 'health' ? 0xdc2626 : 0xf59e0b;
        const mat = new THREE.MeshStandardMaterial({ color, emissive: color, emissiveIntensity: 0.4, roughness: 0.4 });
        const mesh = new THREE.Mesh(new THREE.OctahedronGeometry(0.32, 0), mat);
        mesh.position.y = 0.1;
        group.add(mesh);
        group.userData.spin = true;
        return group;
    }

    updateBoss() {
        const boss = this.state.boss || null;

        // Bersihkan bila tak ada bos aktif.
        if (!boss) {
            if (this.bossObject) {
                this.bossGroup.remove(this.bossObject);
                this.bossObject = null;
                this.bossObjectKey = '';
            }
            return;
        }

        const key = `boss-${boss.avatar || 'boss'}-${boss.size || 3}`;
        if (!this.bossObject || this.bossObjectKey !== key) {
            if (this.bossObject) {
                this.bossGroup.remove(this.bossObject);
            }
            if (this.bossHpBar) {
                this.bossGroup.remove(this.bossHpBar);
                this.bossHpBar = null;
            }
            this.bossObject = this.makeBossMarker(boss);
            this.bossObjectKey = key;
            this.bossGroup.add(this.bossObject);

            // Bar HP mengambang di atas label bos (objek terpisah agar tak ikut berputar).
            this.bossHpBar = this.makeBossHpBar();
            this.bossGroup.add(this.bossHpBar);
        }

        // Bos menempati 1 sel (single block); posisikan tepat di sel-nya.
        const pos = this.tileToWorld(Number(boss.x), Number(boss.y));
        this.setDynamicTarget(this.bossObject, pos, 400);
        this.bossObject.visible = true;

        // Update bar HP: ikuti posisi bos & isi sesuai hp.
        if (this.bossHpBar) {
            const scale = Math.max(1, Number(boss.size) || 3) * 0.55 + 1;
            const barY = 2.35 * scale + 0.55;
            this.bossHpBar.position.set(this.bossObject.position.x, barY, this.bossObject.position.z);
            const pct = Number(boss.max_hp) > 0 ? clamp(Number(boss.hp) / Number(boss.max_hp), 0, 1) : 0;
            const fill = this.bossHpBar.userData.fill;
            const BAR_W = 1.6;
            if (fill) {
                fill.scale.x = Math.max(0.001, BAR_W * pct);
                fill.position.x = -(BAR_W * (1 - pct)) / 2;
                // Warna: hijau→kuning→merah seiring HP turun.
                const color = pct > 0.5 ? 0x22c55e : pct > 0.25 ? 0xf59e0b : 0xdc2626;
                if (fill.material) fill.material.color.setHex(color);
            }
            this.bossHpBar.visible = true;
        }
    }

    makeBossHpBar() {
        const group = new THREE.Group();
        const BAR_W = 1.6;
        const BAR_H = 0.22;

        const bg = new THREE.Sprite(new THREE.SpriteMaterial({ color: 0x1f2937, depthTest: false, transparent: true, opacity: 0.85 }));
        bg.scale.set(BAR_W + 0.12, BAR_H + 0.1, 1);
        bg.position.set(0, 0, 0);

        const fill = new THREE.Sprite(new THREE.SpriteMaterial({ color: 0x22c55e, depthTest: false, transparent: true }));
        fill.scale.set(BAR_W, BAR_H, 1);
        fill.position.set(0, 0, 0.001);

        group.add(bg, fill);
        group.userData.fill = fill;
        group.renderOrder = 999;
        return group;
    }

    makeBossMarker(boss = {}) {
        const group = this.makeHumanoidMarker({
            avatar: boss.avatar || '👹',
            label: boss.nama || 'Bos',
            primary: 0x7f1d1d,
            secondary: 0xdc2626,
            skin: 0x9a3412,
            labelColor: 0x991b1b,
            opacity: 1,
        });

        // Tanduk & mahkota agar bos terlihat garang.
        const hornMat = new THREE.MeshStandardMaterial({ color: 0x1c1917, roughness: 0.5, metalness: 0.2 });
        const leftHorn = new THREE.Mesh(new THREE.ConeGeometry(0.09, 0.42, 12), hornMat);
        const rightHorn = new THREE.Mesh(new THREE.ConeGeometry(0.09, 0.42, 12), hornMat);
        leftHorn.position.set(-0.2, 2.1, 0); leftHorn.rotation.z = 0.5;
        rightHorn.position.set(0.2, 2.1, 0); rightHorn.rotation.z = -0.5;
        // Aura merah menyala di kaki.
        const aura = new THREE.Mesh(
            new THREE.RingGeometry(0.5, 0.75, 32),
            new THREE.MeshBasicMaterial({ color: 0xdc2626, transparent: true, opacity: 0.4, side: THREE.DoubleSide, depthWrite: false }),
        );
        aura.rotation.x = -Math.PI / 2;
        aura.position.y = 0.04;
        const glow = new THREE.PointLight(0xdc2626, 0.9, 5);
        glow.position.y = 1.4;
        group.add(leftHorn, rightHorn, aura, glow);
        group.userData.bossAura = aura;

        // Skala visual saja (bos tetap 1 sel di logika gerak).
        const scale = Math.max(1, Number(boss.size) || 3) * 0.55 + 1;
        group.scale.set(scale, scale, scale);
        return group;
    }

    makeMinionMarker() {
        const group = this.makeHumanoidMarker({
            avatar: '', label: '', primary: 0x7c3aed, secondary: 0xa855f7, skin: 0x6d28d9, labelColor: 0x6d28d9, opacity: 0.98,
        });
        // Antena kecil biar mirip makhluk kecil.
        const antMat = new THREE.MeshStandardMaterial({ color: 0x4c1d95, roughness: 0.5 });
        const ant = new THREE.Mesh(new THREE.ConeGeometry(0.05, 0.24, 8), antMat);
        ant.position.set(0, 2.08, 0);
        group.add(ant);
        group.scale.set(0.68, 0.68, 0.68);
        return group;
    }

    syncCollection(cache, group, items, keyFn, createFn, updateFn, releaseFn = null) {
        const liveKeys = new Set();
        items.forEach((item, index) => {
            const key = keyFn(item, index);
            liveKeys.add(key);
            let object = cache.get(key);
            if (!object) {
                object = createFn(item, index);
                cache.set(key, object);
                group.add(object);
            }
            updateFn(object, item, index);
        });

        Array.from(cache.entries()).forEach(([key, object]) => {
            if (!liveKeys.has(key)) {
                group.remove(object);
                if (releaseFn) {
                    releaseFn(object);
                } else {
                    this.disposeObject(object);
                }
                cache.delete(key);
            }
        });
    }

    updateHud() {
        const answered = Number(this.state.answeredCount ?? (this.state.session?.answered_npcs || []).length);
        const total = Number(this.state.totalNpcs ?? (this.state.npcs || []).filter((npc) => npc.is_active !== false).length);
        const avatar = this.state.character?.avatar_display || this.state.character?.avatar || this.state.playerAvatar || '?';
        this.hudPlayerAvatar.textContent = avatar;
        this.hudNpc.textContent = `${answered}/${total}`;
        const bossActive = !!this.state.boss;
        this.hudAmmo.textContent = bossActive ? '∞' : String(Number(this.state.ammo || 0));
        this.hudShield.textContent = this.state.shieldActive ? `${Number(this.state.shieldSecondsLeft || 0)}d` : 'OFF';
        this.updatePlayerEquipment();
    }

    updatePlayerEquipment() {
        if (!this.playerViewModel) {
            return;
        }

        const weapon = this.playerViewModel.userData.weapon;
        const shield = this.playerViewModel.userData.shield;
        if (weapon) {
            // Saat lawan bos, senjata selalu tampil (peluru tak terbatas).
            weapon.visible = !!this.state.boss || Number(this.state.ammo || 0) > 0;
        }
        if (shield) {
            shield.visible = !!this.state.shieldActive;
        }
    }

    isDialogOpen() {
        return !!((this.state.npcDialogOpen && this.state.currentNpc) || this.state.completionOpen);
    }

    updateDialog() {
        if (!this.dialogHost) {
            return;
        }

        const npc = this.state.currentNpc;
        const completionOpen = !!this.state.completionOpen;
        const open = !!(this.state.npcDialogOpen && npc);

        if (!open && !completionOpen) {
            this.dialogHost.hidden = true;
            this.dialogHost.innerHTML = '';
            this.lastDialogKey = '';
            return;
        }

        this.controlState.forward = false;
        this.controlState.back = false;
        this.controlState.strafeLeft = false;
        this.controlState.strafeRight = false;
        this.controlState.turnLeft = false;
        this.controlState.turnRight = false;

        if (completionOpen) {
            const answered = Number(this.state.answeredCount ?? (this.state.session?.answered_npcs || []).length);
            const total = Number(this.state.totalNpcs ?? (this.state.npcs || []).filter((item) => item.is_active !== false).length);
            const score = Number(this.state.session?.total_score || 0);
            const key = JSON.stringify({
                completion: true,
                score,
                answered,
                total,
                mapName: this.state.mapName || this.state.map?.nama || '',
            });

            if (key === this.lastDialogKey) {
                this.dialogHost.hidden = false;
                return;
            }

            this.lastDialogKey = key;
            const resetActionHtml = this.canReset
                ? '<button type="button" class="is-secondary" data-rpg-3d-reset>Main ulang</button>'
                : '';

            this.dialogHost.innerHTML = `
                <div class="pkg-rpg-3d-dialog-backdrop">
                    <section class="pkg-rpg-3d-dialog-card pkg-rpg-3d-completion-card" aria-label="Game selesai">
                        <div class="pkg-rpg-3d-completion-body">
                            <h2>Game selesai</h2>
                            <p>${escapeHtml(this.state.mapName || this.state.map?.nama || 'Quest')}</p>
                            <div class="pkg-rpg-3d-score-box">
                                <span>Total skor</span>
                                <strong>${score}</strong>
                                <small>dari ${total || answered} pertanyaan</small>
                            </div>
                            <div class="pkg-rpg-3d-completion-actions">
                                <button type="button" class="is-secondary" data-rpg-3d-map-list="${escapeHtml(this.state.mapListUrl || '')}">Peta lain</button>
                                ${resetActionHtml}
                                <button type="button" data-rpg-3d-close-completion>Lihat peta</button>
                            </div>
                        </div>
                    </section>
                </div>
            `;
            this.dialogHost.hidden = false;
            return;
        }

        const choices = Array.isArray(npc.pilihan_jawaban) ? npc.pilihan_jawaban : [];
        const answerResult = this.state.answerResult || null;
        const key = JSON.stringify({
            id: npc.id,
            result: answerResult ? { correct: !!answerResult.correct, poin: Number(answerResult.poin || 0) } : null,
            submitting: !!this.state.submittingAnswer,
            choices,
        });

        if (key === this.lastDialogKey) {
            this.dialogHost.hidden = false;
            return;
        }

        this.lastDialogKey = key;
        const avatar = escapeHtml(npc.avatar_display || npc.avatar || 'NPC');
        const choiceHtml = choices.map((choice, index) => `
            <button type="button" class="pkg-rpg-3d-choice" data-rpg-3d-answer="${index}" ${this.state.submittingAnswer ? 'disabled' : ''}>
                <span>${String.fromCharCode(65 + index)}</span>
                ${escapeHtml(choice)}
            </button>
        `).join('');

        const resultHtml = answerResult ? `
            <div class="pkg-rpg-3d-answer-result ${answerResult.correct ? 'is-correct' : 'is-wrong'}">
                <strong>${answerResult.correct ? 'Benar' : 'Kurang tepat'}</strong>
                <p>${answerResult.correct ? `+${Number(answerResult.poin || npc.poin || 0)} poin` : 'Kamu bisa coba lagi nanti.'}</p>
                <button type="button" data-rpg-3d-close-dialog>${answerResult.correct ? 'Lanjutkan' : 'Coba lagi nanti'}</button>
            </div>
        ` : `<div class="pkg-rpg-3d-choice-list">${choiceHtml}</div>`;

        this.dialogHost.innerHTML = `
            <div class="pkg-rpg-3d-dialog-backdrop">
                <section class="pkg-rpg-3d-dialog-card" aria-label="Pertanyaan NPC">
                    <header>
                        <span>${avatar}</span>
                        <div>
                            <strong>${escapeHtml(npc.nama || 'NPC')}</strong>
                            <small>${Number(npc.poin || 0)} poin</small>
                        </div>
                    </header>
                    <div class="pkg-rpg-3d-dialog-body">
                        <div class="pkg-rpg-3d-question">${escapeHtml(npc.pertanyaan || '')}</div>
                        ${resultHtml}
                    </div>
                </section>
            </div>
        `;
        this.dialogHost.hidden = false;
    }

    updateDirectionHud() {
        const direction = DIRECTIONS[this.headingIndex];
        if (this.headingLabel) {
            this.headingLabel.textContent = direction.label;
        }

        if (this.noticeText && Date.now() < this.noticeUntil) {
            if (this.targetLabel) {
                this.targetLabel.textContent = this.noticeText;
            }
            return;
        }

        const target = this.findNearestUnansweredNpc();
        if (this.targetLabel) {
            if (!target) {
                this.targetLabel.textContent = 'Target: semua NPC selesai atau belum tersedia';
            } else {
                this.targetLabel.textContent = `Target: NPC ${target.distance} langkah ke ${target.label}`;
            }
        }
    }

    findNearestUnansweredNpc() {
        const session = this.state.session || { pos_x: 0, pos_y: 0, answered_npcs: [] };
        const answered = new Set((session.answered_npcs || []).map((id) => Number(id)));
        const playerX = Number(session.pos_x || 0);
        const playerY = Number(session.pos_y || 0);
        const candidates = (this.state.npcs || [])
            .filter((npc) => npc.is_active !== false && !answered.has(Number(npc.id)))
            .map((npc) => {
                const dx = Number(npc.pos_x || 0) - playerX;
                const dy = Number(npc.pos_y || 0) - playerY;
                const distance = Math.abs(dx) + Math.abs(dy);
                let label = '';
                if (Math.abs(dx) >= Math.abs(dy) && dx !== 0) {
                    label = dx > 0 ? 'Timur' : 'Barat';
                } else if (dy !== 0) {
                    label = dy > 0 ? 'Utara' : 'Selatan';
                } else {
                    label = 'posisi ini';
                }
                return { distance, label };
            })
            .sort((a, b) => a.distance - b.distance);

        return candidates[0] || null;
    }

    updateMinimap() {
        if (!this.minimap) {
            return;
        }

        const gridSize = Number(this.state.map?.grid_size || this.state.gridSize || 10);
        const session = this.state.session || { pos_x: 0, pos_y: 0, answered_npcs: [] };
        const answered = new Set((session.answered_npcs || []).map((id) => Number(id)));
        const obstacles = new Set((this.state.obstacles || []).map((item) => `${Number(item.x)},${Number(item.y)}`));
        const enemies = new Set((this.state.enemies || []).map((item) => `${Number(item.x)},${Number(item.y)}`));
        const onlinePlayers = new Set((this.state.onlinePlayers || this.state.online_players || []).map((item) => `${Number(item.pos_x)},${Number(item.pos_y)}`));
        const pickups = new Set([
            ...(this.state.pickups?.shield || []),
            ...(this.state.pickups?.ammo || []),
        ].map((item) => `${Number(item.x)},${Number(item.y)}`));
        const npcs = new Map((this.state.npcs || []).map((npc) => [
            `${Number(npc.pos_x)},${Number(npc.pos_y)}`,
            npc,
        ]));
        const minimapKey = JSON.stringify({
            gridSize,
            player: [Number(session.pos_x), Number(session.pos_y)],
            answered: Array.from(answered).sort(),
            obstacles: Array.from(obstacles).sort(),
            enemies: Array.from(enemies).sort(),
            onlinePlayers: Array.from(onlinePlayers).sort(),
            pickups: Array.from(pickups).sort(),
            npcs: Array.from(npcs.keys()).sort(),
        });

        if (minimapKey === this.lastMinimapKey) {
            return;
        }

        this.lastMinimapKey = minimapKey;

        this.minimap.style.gridTemplateColumns = `repeat(${gridSize}, minmax(0, 1fr))`;
        const cells = [];
        for (let y = gridSize - 1; y >= 0; y -= 1) {
            for (let x = 0; x < gridSize; x += 1) {
                const key = `${x},${y}`;
                let className = 'pkg-rpg-3d-map-cell';
                if (obstacles.has(key)) {
                    className += ' is-wall';
                } else if (Number(session.pos_x) === x && Number(session.pos_y) === y) {
                    className += ' is-player';
                } else if (onlinePlayers.has(key)) {
                    className += ' is-online';
                } else if (enemies.has(key)) {
                    className += ' is-enemy';
                } else if (npcs.has(key)) {
                    className += answered.has(Number(npcs.get(key).id)) ? ' is-npc-done' : ' is-npc';
                } else if (pickups.has(key)) {
                    className += ' is-pickup';
                }
                cells.push(`<span class="${className}"></span>`);
            }
        }

        this.minimap.innerHTML = cells.join('');
    }

    updateCameraTarget() {
        const session = this.state.session || { pos_x: 0, pos_y: 0 };
        const cell = {
            x: Number(session.pos_x || 0),
            y: Number(session.pos_y || 0),
        };
        const pos = this.tileToWorld(cell.x, cell.y);
        const stateTarget = new THREE.Vector3(pos.x, CAMERA_HEIGHT, pos.z);
        const stateKey = this.cellKey(cell);
        const currentKey = this.currentPlayerCell ? this.cellKey(this.currentPlayerCell) : '';

        if (!this.playerInitialized || this.readOnly) {
            this.snapPlayerToCell(cell);
            return;
        }

        if (stateKey !== currentKey && stateKey !== this.lastDispatchedCellKey) {
            const distance = this.playerVisual.distanceTo(stateTarget);
            if (distance > PLAYER_SNAP_DISTANCE || !this.playerMotion.moving) {
                this.snapPlayerToCell(cell);
            }
        }
    }

    advancePlayerVisual(delta) {
        const beforeX = this.playerVisual.x;
        const beforeZ = this.playerVisual.z;
        const lerpAmount = 1 - Math.exp(-PLAYER_IDLE_LERP * delta);
        this.playerVisual.lerp(this.cameraTarget, lerpAmount);

        const moved = Math.hypot(this.playerVisual.x - beforeX, this.playerVisual.z - beforeZ);
        const remaining = Math.hypot(this.cameraTarget.x - this.playerVisual.x, this.cameraTarget.z - this.playerVisual.z);

        this.playerMotion.moving = moved > 0.002 || remaining > 0.035 || this.playerVelocity.lengthSq() > 0.0004;
        if (this.playerMotion.moving) {
            this.playerMotion.stride += moved;
        }
    }

    updateCamera(delta) {
        this.advancePlayerVisual(delta);
        const bob = this.playerMotion.moving
            ? Math.sin(this.playerMotion.stride * 3.8) * 0.035
            : Math.sin(this.clock.elapsedTime * 1.8) * 0.008;

        this.camera.position.set(this.playerVisual.x, CAMERA_HEIGHT + bob, this.playerVisual.z);

        const direction = this.directionVectorFromYaw();
        const lookAt = this.cameraLookTarget;
        lookAt.set(
            this.camera.position.x + direction.dx * TILE_SIZE,
            CAMERA_HEIGHT * 0.92,
            this.camera.position.z + direction.dz * TILE_SIZE,
        );
        this.camera.lookAt(lookAt);

        if (this.shieldAura) {
            this.shieldAura.position.set(this.playerVisual.x, CAMERA_HEIGHT * 0.55, this.playerVisual.z);
        }
    }

    updateContinuousControls(delta) {
        if (this.readOnly || this.isDialogOpen()) {
            this.playerVelocity.multiplyScalar(0);
            return;
        }

        const turnDirection = (this.controlState.turnRight ? 1 : 0) - (this.controlState.turnLeft ? 1 : 0);
        if (turnDirection !== 0) {
            this.yaw += turnDirection * TURN_SPEED * delta;
            this.syncHeadingFromYaw();
            this.updateDirectionHud();
        }

        const touchMove = this.touchControls.move;
        const hasTouchMove = touchMove.active && Math.hypot(touchMove.axisX || 0, touchMove.axisY || 0) > TOUCH_MOVE_DEADZONE;
        const forward = hasTouchMove
            ? (Math.abs(touchMove.axisY) > TOUCH_MOVE_DEADZONE ? -touchMove.axisY : 0)
            : (this.controlState.forward ? 1 : 0) - (this.controlState.back ? 1 : 0);
        const strafe = hasTouchMove
            ? (Math.abs(touchMove.axisX) > TOUCH_MOVE_DEADZONE ? touchMove.axisX : 0)
            : (this.controlState.strafeRight ? 1 : 0) - (this.controlState.strafeLeft ? 1 : 0);
        const targetVelocity = new THREE.Vector3();

        if (forward !== 0 || strafe !== 0) {
            const sin = Math.sin(this.yaw);
            const cos = Math.cos(this.yaw);
            targetVelocity.x = ((sin * forward) + (cos * strafe)) * MOVE_SPEED;
            targetVelocity.z = ((-cos * forward) + (sin * strafe)) * MOVE_SPEED;
            if (targetVelocity.length() > MOVE_SPEED) {
                targetVelocity.setLength(MOVE_SPEED);
            }
        }

        const lerpAmount = 1 - Math.exp(-DAMPING * delta);
        this.playerVelocity.lerp(targetVelocity, lerpAmount);

        if (this.playerVelocity.lengthSq() > 0.0004) {
            this.movePlayerWorld(this.playerVelocity.x * delta, this.playerVelocity.z * delta);
            this.playFootstepIfNeeded();
        }
    }

    movePlayerWorld(dx, dz) {
        let moved = false;
        const nextX = this.playerVisual.x + dx;
        if (!this.isBlockedWorld(nextX, this.playerVisual.z)) {
            this.playerVisual.x = nextX;
            moved = moved || Math.abs(dx) > 0.0001;
        } else {
            this.playerVelocity.x = 0;
        }

        const nextZ = this.playerVisual.z + dz;
        if (!this.isBlockedWorld(this.playerVisual.x, nextZ)) {
            this.playerVisual.z = nextZ;
            moved = moved || Math.abs(dz) > 0.0001;
        } else {
            this.playerVelocity.z = 0;
        }

        if (!moved) {
            return false;
        }

        this.cameraTarget.copy(this.playerVisual);
        this.playerMotion.stride += Math.hypot(dx, dz);
        this.syncPlayerCellFromWorld();
        return true;
    }

    syncPlayerCellFromWorld() {
        const cell = this.worldToCell(this.playerVisual.x, this.playerVisual.z);
        if (!cell || this.isObstacleCell(cell.x, cell.y)) {
            return;
        }

        const nextKey = this.cellKey(cell);
        const currentKey = this.currentPlayerCell ? this.cellKey(this.currentPlayerCell) : '';
        if (nextKey === currentKey) {
            return;
        }

        const stateCell = {
            x: Number(this.state.session?.pos_x ?? this.currentPlayerCell?.x ?? 0),
            y: Number(this.state.session?.pos_y ?? this.currentPlayerCell?.y ?? 0),
        };
        const dx = cell.x - stateCell.x;
        const dy = cell.y - stateCell.y;

        this.currentPlayerCell = cell;
        this.lastDispatchedCellKey = nextKey;
        if (dx !== 0 || dy !== 0) {
            this.dispatchMove(dx, dy);
        }
    }

    snapPlayerToCell(cell) {
        const safeCell = {
            x: clamp(Math.round(Number(cell?.x ?? 0)), 0, this.gridSize() - 1),
            y: clamp(Math.round(Number(cell?.y ?? 0)), 0, this.gridSize() - 1),
        };
        const pos = this.tileToWorld(safeCell.x, safeCell.y);
        this.playerVisual.set(pos.x, CAMERA_HEIGHT, pos.z);
        this.cameraTarget.copy(this.playerVisual);
        this.camera.position.copy(this.playerVisual);
        this.playerVelocity.set(0, 0, 0);
        this.currentPlayerCell = safeCell;
        this.lastDispatchedCellKey = this.cellKey(safeCell);
        this.playerInitialized = true;
        this.playerMotion.moving = false;
    }

    worldToCell(x, z) {
        const center = (this.gridSize() - 1) / 2;
        const cell = {
            x: Math.round((Number(x) / TILE_SIZE) + center),
            y: Math.round(center - (Number(z) / TILE_SIZE)),
        };

        if (cell.x < 0 || cell.x >= this.gridSize() || cell.y < 0 || cell.y >= this.gridSize()) {
            return null;
        }

        return cell;
    }

    isBlockedWorld(x, z) {
        const samples = [
            [x - PLAYER_RADIUS, z - PLAYER_RADIUS],
            [x + PLAYER_RADIUS, z - PLAYER_RADIUS],
            [x - PLAYER_RADIUS, z + PLAYER_RADIUS],
            [x + PLAYER_RADIUS, z + PLAYER_RADIUS],
        ];

        return samples.some(([sampleX, sampleZ]) => {
            const cell = this.worldToCell(sampleX, sampleZ);
            return !cell || this.isObstacleCell(cell.x, cell.y);
        });
    }

    isObstacleCell(x, y) {
        return (this.state.obstacles || []).some((obstacle) => Number(obstacle.x) === x && Number(obstacle.y) === y);
    }

    gridSize() {
        return Number(this.state.map?.grid_size || this.state.gridSize || 10);
    }

    cellKey(cell) {
        return `${Number(cell?.x || 0)},${Number(cell?.y || 0)}`;
    }

    directionVectorFromYaw() {
        return {
            dx: Math.sin(this.yaw),
            dz: -Math.cos(this.yaw),
        };
    }

    cardinalFromYaw() {
        const x = Math.sin(this.yaw);
        const y = Math.cos(this.yaw);

        if (Math.abs(x) > Math.abs(y)) {
            return x > 0 ? DIRECTIONS[1] : DIRECTIONS[3];
        }

        return y > 0 ? DIRECTIONS[0] : DIRECTIONS[2];
    }

    strafeFromDirection(direction, side) {
        if (side < 0) {
            return { dx: -direction.dy, dy: direction.dx };
        }

        return { dx: direction.dy, dy: -direction.dx };
    }

    syncHeadingFromYaw() {
        const direction = this.cardinalFromYaw();
        this.headingIndex = DIRECTIONS.findIndex((item) => item.dx === direction.dx && item.dy === direction.dy);
        if (this.headingIndex < 0) {
            this.headingIndex = 0;
        }
    }

    animateMarkers(delta) {
        const elapsed = this.clock.elapsedTime;
        const moveLerp = 1 - Math.exp(-DYNAMIC_VISUAL_LERP * delta);
        this.npcGroup.children.forEach((object, index) => {
            this.animateHumanoidObject(object, delta, elapsed, false, index);
        });
        this.enemyGroup.children.forEach((object, index) => {
            const moving = this.advanceDynamicObject(object, moveLerp);
            this.animateHumanoidObject(object, delta, elapsed, moving, index + 10);
        });
        this.bossGroup.children.forEach((object, index) => {
            if (object === this.bossHpBar) return; // bar HP tak perlu animasi humanoid
            if (object.userData && object.userData.humanoidParts) {
                // Bos & minion (humanoid) → gerak + animasi jalan.
                const moving = this.advanceDynamicObject(object, moveLerp);
                this.animateHumanoidObject(object, delta, elapsed, moving, index + 40);
            } else {
                // Proyektil dsb → cukup interpolasi posisi bila punya target.
                this.advanceDynamicObject(object, moveLerp);
            }
        });
        // Bar HP mengikuti posisi bos yang sudah teranimasi (halus).
        if (this.bossHpBar && this.bossObject && this.bossObject.visible) {
            this.bossHpBar.position.x = this.bossObject.position.x;
            this.bossHpBar.position.z = this.bossObject.position.z;
        }
        this.playerGroup.children.forEach((object, index) => {
            const moving = this.advanceDynamicObject(object, moveLerp);
            this.animateHumanoidObject(object, delta, elapsed, moving, index + 20);
        });
        this.pickupGroup.children.forEach((object) => {
            object.rotation.y += delta * 2.2;
            object.position.y = 0.12 + Math.sin(elapsed * 3.4) * 0.06;
        });
        this.shieldAura.rotation.y += delta;
        if (this.playerViewModel) {
            const moving = this.playerMotion.moving || this.controlState.forward || this.controlState.back || this.controlState.strafeLeft || this.controlState.strafeRight;
            this.playerViewModel.position.y = moving ? Math.sin(elapsed * 8) * 0.025 : Math.sin(elapsed * 1.8) * 0.01;
            const weapon = this.playerViewModel.userData.weapon;
            if (weapon) {
                if (!weapon.userData.basePosition) {
                    weapon.userData.basePosition = weapon.position.clone();
                    weapon.userData.baseRotation = weapon.rotation.clone();
                }
                const startedAt = Number(weapon.userData.kickStartedAt || 0);
                const progress = startedAt ? clamp((performance.now() - startedAt) / 180, 0, 1) : 1;
                const kick = progress < 1 ? Math.sin(progress * Math.PI) : 0;
                weapon.position.copy(weapon.userData.basePosition);
                weapon.rotation.copy(weapon.userData.baseRotation);
                weapon.position.z += kick * 0.12;
                weapon.rotation.x -= kick * 0.16;
                if (progress >= 1) {
                    weapon.userData.kickStartedAt = 0;
                }
            }
        }
    }

    animate() {
        const delta = Math.min(this.clock.getDelta(), 0.08);
        const now = performance.now();

        if (now - this.lastStateSyncAt >= STATE_SYNC_INTERVAL_MS) {
            this.lastStateSyncAt = now;
            this.syncState(false);
        }

        if (this.root.offsetWidth > 0 && this.root.offsetHeight > 0) {
            this.updateContinuousControls(delta);
            this.updateCamera(delta);
            this.animateMarkers(delta);
            this.updatePlayerShots(delta);
            this.renderer.render(this.scene, this.camera);
            this.updateAdaptiveQuality();
        }

        this.animationFrame = window.requestAnimationFrame(() => this.animate());
    }

    tileToWorld(x, y) {
        const gridSize = Number(this.state.map?.grid_size || this.state.gridSize || 10);
        const center = (gridSize - 1) / 2;
        return {
            x: (x - center) * TILE_SIZE,
            z: (center - y) * TILE_SIZE,
        };
    }

    setDynamicTarget(object, pos, duration = 620) {
        const targetX = Number(pos.x);
        const targetZ = Number(pos.z);
        if (!Number.isFinite(targetX) || !Number.isFinite(targetZ)) {
            return;
        }

        const hasTarget = Number.isFinite(object.userData.targetX) && Number.isFinite(object.userData.targetZ);
        if (!hasTarget) {
            object.position.x = targetX;
            object.position.z = targetZ;
            object.userData.targetX = targetX;
            object.userData.targetZ = targetZ;
            object.userData.moveStartedAt = 0;
            return;
        }

        if (Math.abs(object.userData.targetX - targetX) > 0.001 || Math.abs(object.userData.targetZ - targetZ) > 0.001) {
            object.userData.fromX = object.position.x;
            object.userData.fromZ = object.position.z;
            object.userData.moveStartedAt = performance.now();
            object.userData.moveDuration = Math.max(360, Number(duration || 620));
            object.userData.targetX = targetX;
            object.userData.targetZ = targetZ;
        }
    }

    advanceDynamicObject(object, fallbackLerp = 0.24) {
        if (!Number.isFinite(object.userData.targetX) || !Number.isFinite(object.userData.targetZ)) {
            return false;
        }

        const beforeX = object.position.x;
        const beforeZ = object.position.z;
        const startedAt = Number(object.userData.moveStartedAt || 0);
        const duration = Number(object.userData.moveDuration || 0);

        if (startedAt > 0 && duration > 0) {
            const progress = clamp((performance.now() - startedAt) / duration, 0, 1);
            const eased = progress * progress * (3 - (2 * progress));
            const fromX = Number(object.userData.fromX ?? object.position.x);
            const fromZ = Number(object.userData.fromZ ?? object.position.z);
            object.position.x = fromX + (object.userData.targetX - fromX) * eased;
            object.position.z = fromZ + (object.userData.targetZ - fromZ) * eased;
            if (progress >= 1) {
                object.userData.moveStartedAt = 0;
            }
        } else {
            object.position.x += (object.userData.targetX - object.position.x) * fallbackLerp;
            object.position.z += (object.userData.targetZ - object.position.z) * fallbackLerp;
        }

        const movedX = object.position.x - beforeX;
        const movedZ = object.position.z - beforeZ;
        const moving = Math.hypot(movedX, movedZ) > 0.002;
        if (moving) {
            object.rotation.y = Math.atan2(movedX, movedZ);
        }

        return moving || Math.hypot(object.userData.targetX - object.position.x, object.userData.targetZ - object.position.z) > 0.025;
    }

    updateAdaptiveQuality() {
        const perf = this.performance;
        perf.frames += 1;
        const now = performance.now();
        if (now - perf.lastSampleAt < FPS_SAMPLE_MS) {
            return;
        }

        const fps = perf.frames / ((now - perf.lastSampleAt) / 1000);
        perf.frames = 0;
        perf.lastSampleAt = now;

        let nextRatio = perf.currentPixelRatio;
        if (fps < 42 && perf.currentPixelRatio > 1) {
            nextRatio = Math.max(1, perf.currentPixelRatio - 0.25);
        } else if (fps > 56 && perf.currentPixelRatio < perf.basePixelRatio) {
            nextRatio = Math.min(perf.basePixelRatio, perf.currentPixelRatio + 0.25);
        }

        if (Math.abs(nextRatio - perf.currentPixelRatio) >= 0.05) {
            perf.currentPixelRatio = nextRatio;
            this.renderer.setPixelRatio(nextRatio);
            this.resize();
        }
    }

    makeNpcMarker(npc = {}) {
        return this.makeHumanoidMarker({
            avatar: npc.avatar_display || npc.avatar || 'NPC',
            label: npc.nama || 'NPC',
            primary: 0x2563eb,
            secondary: 0x93c5fd,
            labelColor: 0x1d4ed8,
            opacity: 0.98,
        });
    }

    makeEnemyMarker(enemy = {}) {
        return this.makeHumanoidMarker({
            avatar: enemy.avatar || '!',
            label: 'Musuh',
            primary: 0xdc2626,
            secondary: 0xf97316,
            labelColor: 0x991b1b,
            opacity: 0.98,
        });
    }

    makeOtherPlayerMarker(player = {}) {
        const color = parseHexColor(player.warna, 0x38bdf8);
        return this.makeHumanoidMarker({
            avatar: player.avatar_display || player.avatar || '?',
            label: player.nama || 'Pemain',
            primary: color,
            secondary: mixHexColor(color, 0xffffff, 0.46),
            labelColor: color,
            opacity: 0.42,
        });
    }

    makePickupMarker(type) {
        if (!this.prebuilt.pickup[type]) {
            this.prebuilt.pickup[type] = this.createPickupPrototype(type);
        }

        const group = this.prebuilt.pickup[type].clone(true);
        group.traverse((child) => {
            if (child.material) {
                child.material = child.material.clone();
                if (child.material.map) {
                    child.material.map = child.material.map.clone();
                    child.material.map.needsUpdate = true;
                }
            }
        });
        group.userData.pickupType = type;
        return group;
    }

    createPickupPrototype(type) {
        return type === 'shield' ? this.makeShieldPickupModel() : this.makeAmmoPickupModel();
    }

    acquirePickupObject(type) {
        const bucket = this.pools.pickups[type] || [];
        const object = bucket.pop() || this.makePickupMarker(type);
        object.userData.pickupType = type;
        object.visible = true;
        return object;
    }

    releasePickupObject(object) {
        const type = object.userData.pickupType || 'ammo';
        object.visible = false;
        object.position.set(0, -100, 0);
        if (!this.pools.pickups[type]) {
            this.pools.pickups[type] = [];
        }
        if (this.pools.pickups[type].length < 12) {
            this.pools.pickups[type].push(object);
        } else {
            this.disposeObject(object);
        }
    }

    makeFirstPersonHands() {
        const group = new THREE.Group();
        const color = parseHexColor(this.state.character?.warna, 0x3b82f6);
        const sleeveMaterial = new THREE.MeshStandardMaterial({
            color: mixHexColor(color, 0xffffff, 0.36),
            roughness: 0.62,
            metalness: 0.02,
        });
        const handMaterial = new THREE.MeshStandardMaterial({
            color: 0xf8c9a4,
            roughness: 0.7,
            metalness: 0.01,
        });

        const makeArm = (side) => {
            const arm = new THREE.Group();
            const sleeve = new THREE.Mesh(new THREE.BoxGeometry(0.18, 0.18, 0.48), sleeveMaterial);
            const hand = new THREE.Mesh(new THREE.BoxGeometry(0.19, 0.16, 0.18), handMaterial);
            sleeve.position.z = -0.1;
            hand.position.z = -0.38;
            arm.add(sleeve, hand);
            arm.position.set(side * 0.42, -0.52, -0.78);
            arm.rotation.set(-0.34, side * 0.18, side * 0.08);
            return arm;
        };

        const weapon = this.makeWeaponModel({ firstPerson: true });
        weapon.position.set(0.38, -0.43, -1.12);
        weapon.rotation.set(-0.12, -0.08, 0.02);
        weapon.visible = false;

        const shield = this.makeShieldMesh({ opacity: 0.64, glow: true });
        shield.position.set(-0.48, -0.36, -0.92);
        shield.rotation.set(0.12, 0.28, -0.2);
        shield.scale.setScalar(0.52);
        shield.visible = false;

        group.add(makeArm(-1), makeArm(1), weapon, shield);
        group.userData.weapon = weapon;
        group.userData.shield = shield;
        return group;
    }

    makeWeaponModel(options = {}) {
        const group = new THREE.Group();
        const scale = options.firstPerson ? 1 : 0.78;
        const metal = new THREE.MeshStandardMaterial({ color: 0x1f2937, roughness: 0.42, metalness: 0.34 });
        const grip = new THREE.MeshStandardMaterial({ color: 0x78350f, roughness: 0.72, metalness: 0.04 });
        const accent = new THREE.MeshStandardMaterial({
            color: 0xfacc15,
            emissive: 0xf59e0b,
            emissiveIntensity: 0.08,
            roughness: 0.45,
            metalness: 0.18,
        });

        const barrel = new THREE.Mesh(new THREE.BoxGeometry(0.18, 0.16, 0.86), metal);
        barrel.position.set(0, 0.08, -0.22);
        const muzzle = new THREE.Mesh(new THREE.CylinderGeometry(0.07, 0.07, 0.12, 18), metal);
        muzzle.rotation.x = Math.PI / 2;
        muzzle.position.set(0, 0.08, -0.7);
        const body = new THREE.Mesh(new THREE.BoxGeometry(0.26, 0.24, 0.38), metal);
        body.position.set(0, -0.03, 0.1);
        const handle = new THREE.Mesh(new THREE.BoxGeometry(0.2, 0.48, 0.18), grip);
        handle.position.set(0, -0.34, 0.18);
        handle.rotation.x = -0.38;
        const sight = new THREE.Mesh(new THREE.BoxGeometry(0.09, 0.06, 0.16), accent);
        sight.position.set(0, 0.2, -0.18);

        group.add(barrel, muzzle, body, handle, sight);
        group.scale.setScalar(scale);
        return group;
    }

    makeShieldMesh(options = {}) {
        const group = new THREE.Group();
        const opacity = clamp(Number(options.opacity ?? 1), 0.2, 1);
        const shieldShape = new THREE.Shape();
        shieldShape.moveTo(0, 0.72);
        shieldShape.bezierCurveTo(0.46, 0.62, 0.64, 0.28, 0.54, -0.22);
        shieldShape.bezierCurveTo(0.44, -0.66, 0.16, -0.92, 0, -1.02);
        shieldShape.bezierCurveTo(-0.16, -0.92, -0.44, -0.66, -0.54, -0.22);
        shieldShape.bezierCurveTo(-0.64, 0.28, -0.46, 0.62, 0, 0.72);

        const faceMaterial = new THREE.MeshStandardMaterial({
            color: 0x10b981,
            roughness: 0.46,
            metalness: 0.16,
            transparent: opacity < 1,
            opacity,
            depthWrite: opacity >= 0.65,
            emissive: 0x10b981,
            emissiveIntensity: options.glow ? 0.12 : 0.02,
        });
        const rimMaterial = new THREE.MeshStandardMaterial({
            color: 0xdbeafe,
            roughness: 0.35,
            metalness: 0.28,
            transparent: opacity < 1,
            opacity,
            depthWrite: opacity >= 0.65,
        });

        const face = new THREE.Mesh(
            new THREE.ExtrudeGeometry(shieldShape, { depth: 0.08, bevelEnabled: true, bevelSize: 0.025, bevelThickness: 0.02, bevelSegments: 2 }),
            faceMaterial,
        );
        face.rotation.y = Math.PI;
        face.position.z = 0.04;
        const boss = new THREE.Mesh(new THREE.SphereGeometry(0.18, 24, 12), rimMaterial);
        boss.scale.z = 0.34;
        boss.position.z = -0.08;
        const stripe = new THREE.Mesh(new THREE.BoxGeometry(0.08, 1.24, 0.055), rimMaterial);
        stripe.position.z = -0.1;
        stripe.rotation.z = -0.22;
        group.add(face, boss, stripe);
        return group;
    }

    makeShieldPickupModel() {
        const group = new THREE.Group();
        const shield = this.makeShieldMesh({ glow: true });
        shield.position.y = 1.04;
        shield.rotation.x = -0.08;
        shield.scale.setScalar(0.82);
        const base = new THREE.Mesh(
            new THREE.CylinderGeometry(0.48, 0.62, 0.16, 32),
            new THREE.MeshStandardMaterial({ color: 0x064e3b, roughness: 0.64, metalness: 0.06 }),
        );
        base.position.y = 0.08;
        group.add(base, shield, this.makeTextSprite('Tameng', 0x10b981));
        return group;
    }

    makeAmmoPickupModel() {
        const group = new THREE.Group();
        const weapon = this.makeWeaponModel();
        weapon.position.set(0, 1.03, 0);
        weapon.rotation.set(0.08, Math.PI / 2, -0.1);
        const brass = new THREE.MeshStandardMaterial({
            color: 0xd97706,
            emissive: 0xf59e0b,
            emissiveIntensity: 0.1,
            roughness: 0.34,
            metalness: 0.42,
        });
        const lead = new THREE.MeshStandardMaterial({ color: 0x334155, roughness: 0.38, metalness: 0.38 });
        for (let i = 0; i < 4; i += 1) {
            const bullet = new THREE.Group();
            const casing = new THREE.Mesh(new THREE.CylinderGeometry(0.055, 0.055, 0.48, 16), brass);
            casing.rotation.x = Math.PI / 2;
            const tip = new THREE.Mesh(new THREE.ConeGeometry(0.057, 0.14, 16), lead);
            tip.rotation.x = -Math.PI / 2;
            tip.position.z = -0.31;
            bullet.add(casing, tip);
            bullet.position.set((i - 1.5) * 0.16, 0.36, 0.26 + (i % 2) * 0.1);
            bullet.rotation.y = 0.18 * (i - 1.5);
            group.add(bullet);
        }
        const plate = new THREE.Mesh(
            new THREE.BoxGeometry(1.0, 0.1, 0.72),
            new THREE.MeshStandardMaterial({ color: 0x92400e, roughness: 0.58, metalness: 0.08 }),
        );
        plate.position.y = 0.1;
        group.add(plate, weapon, this.makeTextSprite('Peluru', 0xf59e0b));
        return group;
    }

    makeShieldAura() {
        const group = new THREE.Group();
        const aura = new THREE.Mesh(
            new THREE.SphereGeometry(1.4, 32, 16),
            new THREE.MeshBasicMaterial({
                color: 0x34d399,
                transparent: true,
                opacity: 0.24,
                depthWrite: false,
            }),
        );
        group.add(aura);
        return group;
    }

    makeHumanoidMarker(options = {}) {
        const group = new THREE.Group();
        const opacity = clamp(Number(options.opacity ?? 1), 0.18, 1);
        const primary = Number(options.primary ?? 0x2563eb);
        const secondary = Number(options.secondary ?? mixHexColor(primary, 0xffffff, 0.42));
        const skin = Number(options.skin ?? 0xf8c9a4);
        const dark = mixHexColor(primary, 0x000000, 0.35);
        const material = (color, emissive = 0x000000, emissiveIntensity = 0.03, extra = {}) => new THREE.MeshStandardMaterial({
            color,
            emissive,
            emissiveIntensity,
            roughness: 0.6,
            metalness: 0.05,
            transparent: opacity < 1,
            opacity,
            depthWrite: opacity >= 0.65,
            ...extra,
        });

        const shadow = new THREE.Mesh(
            new THREE.CylinderGeometry(0.6, 0.6, 0.025, 36),
            new THREE.MeshBasicMaterial({ color: 0x020617, transparent: true, opacity: 0.18 * opacity, depthWrite: false }),
        );
        shadow.scale.z = 0.72;
        shadow.position.y = 0.02;

        // Pinggul + torso yang mengecil ke atas (siluet lebih manusiawi).
        const hips = new THREE.Mesh(new THREE.CylinderGeometry(0.26, 0.3, 0.28, 18), material(dark, dark, 0.04));
        hips.position.y = 0.66;
        const torso = new THREE.Mesh(new THREE.CylinderGeometry(0.34, 0.27, 0.72, 20), material(primary, primary, 0.06));
        torso.position.y = 1.12;
        // Dada / pelindung.
        const chest = new THREE.Mesh(new THREE.SphereGeometry(0.34, 20, 14), material(secondary, secondary, 0.05));
        chest.scale.set(1, 0.62, 0.66);
        chest.position.set(0, 1.28, 0.02);
        // Bahu bulat.
        const shoulderGeo = new THREE.SphereGeometry(0.17, 14, 10);
        const lShoulder = new THREE.Mesh(shoulderGeo, material(secondary));
        const rShoulder = new THREE.Mesh(shoulderGeo, material(secondary));
        lShoulder.position.set(-0.36, 1.44, 0);
        rShoulder.position.set(0.36, 1.44, 0);
        // Leher + kepala.
        const neck = new THREE.Mesh(new THREE.CylinderGeometry(0.1, 0.12, 0.14, 12), material(skin));
        neck.position.y = 1.6;
        const head = new THREE.Mesh(new THREE.SphereGeometry(0.3, 24, 18), material(skin));
        head.position.y = 1.82;
        head.scale.set(1, 1.06, 0.98);
        // Rambut / helm sebagai penutup atas kepala (beda warna → wujud lebih jelas).
        const hair = new THREE.Mesh(new THREE.SphereGeometry(0.31, 24, 18, 0, Math.PI * 2, 0, Math.PI * 0.55), material(dark, dark, 0.05));
        hair.position.y = 1.86;
        hair.scale.set(1.02, 1.02, 1);

        // Lengan meruncing + telapak.
        const armGeometry = new THREE.CapsuleGeometry(0.11, 0.5, 6, 14);
        const handGeo = new THREE.SphereGeometry(0.11, 12, 10);
        const leftArm = new THREE.Group();
        const rightArm = new THREE.Group();
        const lArmMesh = new THREE.Mesh(armGeometry, material(primary));
        const rArmMesh = new THREE.Mesh(armGeometry, material(primary));
        lArmMesh.position.y = -0.3; rArmMesh.position.y = -0.3;
        const lHand = new THREE.Mesh(handGeo, material(skin));
        const rHand = new THREE.Mesh(handGeo, material(skin));
        lHand.position.y = -0.6; rHand.position.y = -0.6;
        leftArm.add(lArmMesh, lHand); rightArm.add(rArmMesh, rHand);
        leftArm.position.set(-0.42, 1.44, 0);
        rightArm.position.set(0.42, 1.44, 0);

        // Kaki + sepatu.
        const legGeometry = new THREE.CapsuleGeometry(0.13, 0.54, 6, 14);
        const footGeo = new THREE.BoxGeometry(0.2, 0.12, 0.32);
        const leftLeg = new THREE.Group();
        const rightLeg = new THREE.Group();
        const lLegMesh = new THREE.Mesh(legGeometry, material(dark));
        const rLegMesh = new THREE.Mesh(legGeometry, material(dark));
        lLegMesh.position.y = -0.3; rLegMesh.position.y = -0.3;
        const lFoot = new THREE.Mesh(footGeo, material(0x1e293b));
        const rFoot = new THREE.Mesh(footGeo, material(0x1e293b));
        lFoot.position.set(0, -0.62, 0.06); rFoot.position.set(0, -0.62, 0.06);
        leftLeg.add(lLegMesh, lFoot); rightLeg.add(rLegMesh, rFoot);
        leftLeg.position.set(-0.17, 0.62, 0);
        rightLeg.position.set(0.17, 0.62, 0);

        group.add(
            shadow, hips, torso, chest, lShoulder, rShoulder, neck, head, hair,
            leftArm, rightArm, leftLeg, rightLeg,
            this.makeAvatarBadgeSprite(options.avatar || '?', primary, { opacity, y: 1.86, scale: 0.5 }),
            this.makeTextSprite(options.label || 'NPC', options.labelColor || primary, { opacity, y: 2.4 }),
        );
        group.userData.humanoidParts = { leftArm, rightArm, leftLeg, rightLeg, head };
        group.userData.walkPhase = Math.random() * Math.PI * 2;
        return group;
    }

    animateHumanoidObject(object, delta, elapsed, moving, index = 0) {
        const parts = object.userData.humanoidParts;
        if (!parts) {
            object.position.y = Math.sin(elapsed * 2 + index) * 0.06;
            return;
        }

        object.userData.walkPhase = Number(object.userData.walkPhase || 0) + delta * (moving ? 8.6 : 1.7);
        const phase = object.userData.walkPhase + index * 0.2;
        const stride = moving ? Math.sin(phase) : Math.sin(elapsed * 1.7 + index) * 0.09;
        // Ayun lengan & kaki dari pivot bahu/pinggul (kini Group, jadi rotasi natural).
        parts.leftArm.rotation.x = stride * (moving ? 0.6 : 0.14);
        parts.rightArm.rotation.x = -stride * (moving ? 0.6 : 0.14);
        parts.leftLeg.rotation.x = -stride * (moving ? 0.55 : 0.1);
        parts.rightLeg.rotation.x = stride * (moving ? 0.55 : 0.1);
        // Sedikit gerak lengan ke samping saat diam (bernapas).
        parts.leftArm.rotation.z = 0.06 + (moving ? 0 : Math.sin(elapsed * 1.6) * 0.02);
        parts.rightArm.rotation.z = -0.06 - (moving ? 0 : Math.sin(elapsed * 1.6) * 0.02);
        parts.head.position.y = 1.82 + Math.sin(phase * 2) * (moving ? 0.03 : 0.014);
        object.position.y = Math.sin(elapsed * 2.1 + index) * (moving ? 0.035 : 0.02);
    }

    makeTextSprite(text, color, options = {}) {
        const canvas = document.createElement('canvas');
        canvas.width = 256;
        canvas.height = 96;
        const context = canvas.getContext('2d');
        const opacity = clamp(Number(options.opacity ?? 1), 0.1, 1);
        context.clearRect(0, 0, canvas.width, canvas.height);
        context.fillStyle = `rgba(255, 255, 255, ${0.88 * opacity})`;
        context.strokeStyle = `rgba(15, 23, 42, ${0.18 * opacity})`;
        context.lineWidth = 4;
        roundRect(context, 18, 18, 220, 54, 18);
        context.fill();
        context.stroke();
        context.fillStyle = `#${color.toString(16).padStart(6, '0')}`;
        context.font = '700 28px Inter, Arial, sans-serif';
        context.textAlign = 'center';
        context.textBaseline = 'middle';
        context.fillText(text, canvas.width / 2, 45);

        const texture = new THREE.CanvasTexture(canvas);
        const sprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: texture, transparent: true, opacity }));
        sprite.position.y = Number(options.y ?? 2.35);
        sprite.scale.set(1.8, 0.68, 1);
        return sprite;
    }

    makeAvatarBadgeSprite(text, color, options = {}) {
        const canvas = document.createElement('canvas');
        canvas.width = 192;
        canvas.height = 192;
        const context = canvas.getContext('2d');
        const opacity = clamp(Number(options.opacity ?? 1), 0.1, 1);
        const hex = Number(color || 0x2563eb).toString(16).padStart(6, '0');
        context.clearRect(0, 0, canvas.width, canvas.height);
        context.fillStyle = `rgba(255, 255, 255, ${0.9 * opacity})`;
        context.strokeStyle = `#${hex}`;
        context.lineWidth = 10;
        context.beginPath();
        context.arc(96, 96, 68, 0, Math.PI * 2);
        context.fill();
        context.stroke();
        context.font = '76px "Segoe UI Emoji", "Apple Color Emoji", Arial, sans-serif';
        context.textAlign = 'center';
        context.textBaseline = 'middle';
        context.fillStyle = '#0f172a';
        context.fillText(String(text || '?'), 96, 100);

        const texture = new THREE.CanvasTexture(canvas);
        const sprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: texture, transparent: true, opacity }));
        const scale = Number(options.scale ?? 0.66);
        sprite.position.set(0, Number(options.y ?? 1.72), -0.28);
        sprite.scale.set(scale, scale, 1);
        return sprite;
    }

    makeAvatarSprite(text, color, options = {}) {
        const canvas = document.createElement('canvas');
        canvas.width = 160;
        canvas.height = 160;
        const context = canvas.getContext('2d');
        context.clearRect(0, 0, canvas.width, canvas.height);
        context.fillStyle = options.background || 'rgba(255, 255, 255, 0.92)';
        context.strokeStyle = `#${color.toString(16).padStart(6, '0')}`;
        context.lineWidth = 8;
        context.beginPath();
        context.arc(80, 80, 58, 0, Math.PI * 2);
        context.fill();
        context.stroke();
        context.font = '64px "Segoe UI Emoji", "Apple Color Emoji", Arial, sans-serif';
        context.textAlign = 'center';
        context.textBaseline = 'middle';
        context.fillStyle = '#0f172a';
        context.fillText(String(text || '?'), 80, 82);

        const texture = new THREE.CanvasTexture(canvas);
        const sprite = new THREE.Sprite(new THREE.SpriteMaterial({
            map: texture,
            transparent: true,
            opacity: Number(options.opacity ?? 1),
        }));
        const scale = Number(options.scale || 1.35);
        sprite.position.y = Number(options.y || 1.72);
        sprite.scale.set(scale, scale, 1);
        return sprite;
    }

    prewarmRuntimeAssets() {
        this.prebuilt.pickup.shield = this.createPickupPrototype('shield');
        this.prebuilt.pickup.ammo = this.createPickupPrototype('ammo');
        // Peluru terbang nyata (bola bercahaya + trail). Dipakai berulang lewat pool.
        this.playerShots = [];
        this.shotPool = [];
        this.prebuilt.shotGeometry = new THREE.SphereGeometry(0.12, 12, 10);
        this.prebuilt.shotMaterial = new THREE.MeshBasicMaterial({ color: 0xfde047 });
        this.prebuilt.tracerMaterial = new THREE.MeshBasicMaterial({ color: 0xfbbf24, transparent: true, opacity: 0.5 });
        // Muzzle flash sprite di ujung senjata.
        this.muzzleFlash = new THREE.Sprite(new THREE.SpriteMaterial({ color: 0xfff3c4, transparent: true, opacity: 0, depthWrite: false }));
        this.muzzleFlash.scale.set(0.6, 0.6, 1);
        this.muzzleFlash.visible = false;
        this.scene.add(this.muzzleFlash);
        this.setupAudio();
    }

    acquireShotMesh() {
        let mesh = this.shotPool.pop();
        if (!mesh) {
            mesh = new THREE.Group();
            const core = new THREE.Mesh(this.prebuilt.shotGeometry, this.prebuilt.shotMaterial);
            const tracer = new THREE.Mesh(new THREE.CylinderGeometry(0.045, 0.045, 1, 8), this.prebuilt.tracerMaterial);
            tracer.rotation.x = Math.PI / 2; // sumbu Z
            // Tidak memakai PointLight per peluru (penyebab lag). Material sudah terang sendiri.
            mesh.add(core, tracer);
            mesh.userData.tracer = tracer;
        }
        mesh.visible = true;
        this.scene.add(mesh);
        return mesh;
    }

    releaseShotMesh(mesh) {
        this.scene.remove(mesh);
        mesh.visible = false;
        this.shotPool.push(mesh);
    }

    compileRuntimeAssets() {
        const temporaryVisible = [
            this.playerViewModel?.userData?.weapon,
            this.playerViewModel?.userData?.shield,
        ].filter(Boolean);
        const previousVisibility = temporaryVisible.map((object) => object.visible);
        temporaryVisible.forEach((object) => {
            object.visible = true;
        });

        try {
            this.renderer.compile(this.scene, this.camera);
        } catch (error) {
            // Browsers may skip shader precompilation; rendering can continue normally.
        } finally {
            temporaryVisible.forEach((object, index) => {
                object.visible = previousVisibility[index];
            });
        }
    }

    setupAudio() {
        if (this.audio.context || this.audio.failed) {
            return;
        }

        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (!AudioContextClass) {
            this.audio.failed = true;
            return;
        }

        try {
            const context = new AudioContextClass({ latencyHint: 'interactive' });
            const master = context.createGain();
            master.gain.value = 0.34;
            master.connect(context.destination);

            const noiseBuffer = context.createBuffer(1, Math.max(1, Math.floor(context.sampleRate * 0.32)), context.sampleRate);
            const data = noiseBuffer.getChannelData(0);
            for (let i = 0; i < data.length; i += 1) {
                data[i] = (Math.random() * 2 - 1) * (1 - (i / data.length));
            }

            this.audio.context = context;
            this.audio.master = master;
            this.audio.noiseBuffer = noiseBuffer;
            this.audio.unlocked = context.state === 'running';
            if (this.audio.unlocked) {
                this.startAmbientSound();
            }
        } catch (error) {
            this.audio.failed = true;
        }
    }

    unlockAudio() {
        this.setupAudio();
        const context = this.audio.context;
        if (!context || this.audio.failed) {
            return;
        }

        if (this.audio.unlocked && context.state === 'running') {
            this.startAmbientSound();
            return;
        }

        const unlock = context.state === 'suspended' ? context.resume() : Promise.resolve();
        unlock
            .then(() => {
                this.audio.unlocked = true;
                this.startAmbientSound();
                this.playTone(80, 0.01, 0.0001, 'sine');
            })
            .catch(() => null);
    }

    startAmbientSound() {
        if (!this.audio.context || !this.audio.master || this.audio.ambient) {
            return;
        }

        const context = this.audio.context;
        const oscillator = context.createOscillator();
        const filter = context.createBiquadFilter();
        const gain = context.createGain();
        oscillator.type = 'sine';
        oscillator.frequency.value = 72;
        filter.type = 'lowpass';
        filter.frequency.value = 180;
        gain.gain.value = 0.012;
        oscillator.connect(filter);
        filter.connect(gain);
        gain.connect(this.audio.master);
        oscillator.start();
        this.audio.ambient = { oscillator, gain };
    }

    playStateAudio(previousAmmo, previousShieldActive) {
        const currentAmmo = Number(this.state.ammo || 0);
        const currentShieldActive = !!this.state.shieldActive;
        if (currentAmmo > previousAmmo) {
            this.playSound('pickup-ammo');
        }
        if (!previousShieldActive && currentShieldActive) {
            this.playSound('pickup-shield');
        }
    }

    playSound(name) {
        if (!this.audio.unlocked || !this.audio.context || !this.audio.master) {
            return;
        }

        if (name === 'footstep') {
            this.playNoise(0.055, 0.075, 180);
            this.playTone(92, 0.07, 0.03, 'triangle');
        } else if (name === 'pickup-ammo') {
            this.playTone(420, 0.08, 0.055, 'square');
            window.setTimeout(() => this.playTone(620, 0.09, 0.045, 'triangle'), 70);
        } else if (name === 'pickup-shield') {
            this.playTone(260, 0.12, 0.052, 'sine');
            window.setTimeout(() => this.playTone(520, 0.18, 0.045, 'sine'), 80);
            this.playNoise(0.16, 0.035, 1200);
        } else if (name === 'shot') {
            this.playNoise(0.09, 0.16, 1800);
            this.playTone(126, 0.08, 0.08, 'sawtooth');
        } else if (name === 'miss') {
            this.playTone(190, 0.06, 0.035, 'triangle');
        }
    }

    playFootstepIfNeeded() {
        const now = performance.now();
        if (now - this.lastFootstepAt < FOOTSTEP_INTERVAL_MS) {
            return;
        }

        this.lastFootstepAt = now;
        this.playSound('footstep');
    }

    playTone(frequency, duration, volume, type = 'sine') {
        const context = this.audio.context;
        if (!context || !this.audio.master) {
            return;
        }

        const oscillator = context.createOscillator();
        const gain = context.createGain();
        const now = context.currentTime;
        oscillator.type = type;
        oscillator.frequency.setValueAtTime(frequency, now);
        gain.gain.setValueAtTime(Math.max(0.0001, volume), now);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + Math.max(0.01, duration));
        oscillator.connect(gain);
        gain.connect(this.audio.master);
        oscillator.start(now);
        oscillator.stop(now + duration + 0.02);
    }

    playNoise(duration, volume, filterFrequency = 700) {
        const context = this.audio.context;
        if (!context || !this.audio.master || !this.audio.noiseBuffer) {
            return;
        }

        const source = context.createBufferSource();
        const filter = context.createBiquadFilter();
        const gain = context.createGain();
        const now = context.currentTime;
        source.buffer = this.audio.noiseBuffer;
        filter.type = 'lowpass';
        filter.frequency.setValueAtTime(filterFrequency, now);
        gain.gain.setValueAtTime(Math.max(0.0001, volume), now);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + Math.max(0.01, duration));
        source.connect(filter);
        filter.connect(gain);
        gain.connect(this.audio.master);
        source.start(now);
        source.stop(now + duration + 0.02);
    }

    kickWeapon() {
        const weapon = this.playerViewModel?.userData?.weapon;
        if (weapon) {
            weapon.userData.kickStartedAt = performance.now();
        }
    }

    flashShot(dx, dy) {
        // Tembak manual: arah = pandangan kamera (yaw).
        const dir = this.directionVectorFromYaw ? this.directionVectorFromYaw() : { dx, dz: -1 };
        this.spawnShotVisual(Number(dir.dx || 0), Number(dir.dz || 0));
    }

    // Tembakan visual dari MONCONG SENJATA searah (vx,vz) dunia. Dipakai manual & auto/boss.
    spawnShotVisual(vxRaw, vzRaw) {
        if (!this.playerShots) return;
        let vx = Number(vxRaw || 0);
        let vz = Number(vzRaw || 0);
        const len = Math.hypot(vx, vz) || 1;
        vx /= len; vz /= len;

        // Arah "kanan" relatif untuk menggeser origin ke sisi senjata (kanan-bawah layar).
        const rx = -vz, rz = vx;
        const originX = this.camera.position.x + vx * 1.3 + rx * 0.42;
        const originZ = this.camera.position.z + vz * 1.3 + rz * 0.42;
        const originY = CAMERA_HEIGHT * 0.72;

        // Batasi jumlah peluru aktif agar tak nge-lag saat beruntun.
        if (this.playerShots.length >= 14) {
            const oldest = this.playerShots.shift();
            if (oldest) this.releaseShotMesh(oldest.mesh);
        }

        const mesh = this.acquireShotMesh();
        mesh.position.set(originX, originY, originZ);
        mesh.rotation.set(0, Math.atan2(vx, vz), 0);

        this.playerShots.push({
            mesh,
            x: originX, y: originY, z: originZ,
            vx: vx * SHOT_SPEED, vz: vz * SHOT_SPEED,
            born: performance.now(),
            ttl: 650,
        });

        if (this.muzzleFlash) {
            this.muzzleFlash.position.set(originX, originY, originZ);
            this.muzzleFlash.material.opacity = 0.9;
            this.muzzleFlash.visible = true;
            window.clearTimeout(this._muzzleTimer);
            this._muzzleTimer = window.setTimeout(() => {
                if (this.muzzleFlash) { this.muzzleFlash.material.opacity = 0; this.muzzleFlash.visible = false; }
            }, 60);
        }
    }

    // Tembakan menuju sebuah petak (dipakai auto-shoot / tembak bos dari Alpine).
    fireVisualShotToTile(tx, ty) {
        const session = this.state.session || { pos_x: 0, pos_y: 0 };
        const from = this.tileToWorld(Number(session.pos_x || 0), Number(session.pos_y || 0));
        const to = this.tileToWorld(Number(tx), Number(ty));
        this.spawnShotVisual(to.x - from.x, to.z - from.z);
        this.kickWeapon();
        this.playSound('shot');
    }

    updatePlayerShots(delta) {
        if (!this.playerShots || !this.playerShots.length) return;
        const now = performance.now();
        const survive = [];
        for (const shot of this.playerShots) {
            shot.x += shot.vx * delta;
            shot.z += shot.vz * delta;
            shot.mesh.position.set(shot.x, shot.y, shot.z);
            if (now - shot.born < shot.ttl) {
                survive.push(shot);
            } else {
                this.releaseShotMesh(shot.mesh);
            }
        }
        this.playerShots = survive;
    }

    resize() {
        if (!this.renderer || !this.canvasHost) {
            return;
        }

        const rect = this.root.getBoundingClientRect();
        const width = Math.max(Math.floor(rect.width), 1);
        const height = Math.max(Math.floor(rect.height), 1);
        const canvas = this.renderer.domElement;
        if (canvas.width === Math.floor(width * this.renderer.getPixelRatio()) && canvas.height === Math.floor(height * this.renderer.getPixelRatio())) {
            return;
        }

        this.camera.aspect = width / height;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(width, height, false);
    }

    clearGroup(group) {
        while (group.children.length) {
            const child = group.children.pop();
            this.disposeObject(child);
        }
    }

    disposeObject(object) {
        object.traverse((child) => {
            if (child.geometry) {
                child.geometry.dispose();
            }
            if (child.material) {
                if (Array.isArray(child.material)) {
                    child.material.forEach((material) => {
                        if (material.map) {
                            material.map.dispose();
                        }
                        material.dispose();
                    });
                } else {
                    if (child.material.map) {
                        child.material.map.dispose();
                    }
                    child.material.dispose();
                }
            }
        });
    }
}

function roundRect(context, x, y, width, height, radius) {
    context.beginPath();
    context.moveTo(x + radius, y);
    context.lineTo(x + width - radius, y);
    context.quadraticCurveTo(x + width, y, x + width, y + radius);
    context.lineTo(x + width, y + height - radius);
    context.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
    context.lineTo(x + radius, y + height);
    context.quadraticCurveTo(x, y + height, x, y + height - radius);
    context.lineTo(x, y + radius);
    context.quadraticCurveTo(x, y, x + radius, y);
    context.closePath();
}

function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function parseHexColor(value, fallback) {
    const text = String(value || '').trim();
    const match = text.match(/^#?([0-9a-f]{6})$/i);

    if (!match) {
        return fallback;
    }

    return Number.parseInt(match[1], 16);
}

function hexColorToCss(color) {
    return `#${Number(color || 0xffffff).toString(16).padStart(6, '0')}`;
}

function darkenHexColor(color, factor = 0.75) {
    const hex = Number(color || 0);
    const r = Math.max(0, Math.min(255, Math.round(((hex >> 16) & 255) * factor)));
    const g = Math.max(0, Math.min(255, Math.round(((hex >> 8) & 255) * factor)));
    const b = Math.max(0, Math.min(255, Math.round((hex & 255) * factor)));
    return (r << 16) + (g << 8) + b;
}

function mixHexColor(color, target, amount = 0.5) {
    const ratio = clamp(Number(amount || 0), 0, 1);
    const source = Number(color || 0);
    const dest = Number(target || 0xffffff);
    const sr = (source >> 16) & 255;
    const sg = (source >> 8) & 255;
    const sb = source & 255;
    const dr = (dest >> 16) & 255;
    const dg = (dest >> 8) & 255;
    const db = dest & 255;
    const r = Math.round(sr + (dr - sr) * ratio);
    const g = Math.round(sg + (dg - sg) * ratio);
    const b = Math.round(sb + (db - sb) * ratio);
    return (r << 16) + (g << 8) + b;
}

export function bootRpgThreeScene(root) {
    if (!root || root.__pkgRpgThreeScene) {
        return root?.__pkgRpgThreeScene || null;
    }

    try {
        root.__pkgRpgThreeScene = new RpgThreeScene(root);
        return root.__pkgRpgThreeScene;
    } catch (error) {
        console.error('RPG 3D scene failed to start', error);
        root.classList.add('pkg-rpg-3d-error');
        root.innerHTML = '<div class="pkg-rpg-3d-fallback">Tampilan 3D belum bisa dimuat di perangkat ini.</div>';
        throw error;
    }
}

export function bootRpgThreeScenes(scope = document) {
    return Array.from(scope.querySelectorAll('[data-rpg-3d-scene]')).map((root) => bootRpgThreeScene(root));
}

if (!window.__pkgRpg3dLazyLoader) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => bootRpgThreeScenes());
    } else {
        bootRpgThreeScenes();
    }
}
