import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';

const TILE_SIZE = 4;
const PLAYER_HEIGHT = 1.55;
const PLAYER_RADIUS = 0.42;
const MOVE_SPEED = 4.8;
const TURN_SPEED = Math.PI * 1.45;
const DAMPING = 12;
const DYNAMIC_VISUAL_LERP = 5.2;
const AUTO_SHOOT_RANGE = 3;
const PICKUP_RESPAWN_MS = 9000;
const ONLINE_STATE_POLL_MS = 1800;
const ONLINE_MOVE_SYNC_MS = 520;
const FOOTSTEP_INTERVAL_MS = 360;
const FPS_SAMPLE_MS = 900;

const THEME_PALETTES = {
    grass: {
        sky: 0x9ed6bf,
        fog: 0xcff5df,
        floor: ['#5aa06b', '#447e56', '#2f573f'],
        wall: 0x31513d,
        wallCap: 0x20382b,
        wallBase: 0x182820,
        ceiling: 0xb8dec6,
        npc: 0x2563eb,
        npcAccent: 0x93c5fd,
    },
    desert: {
        sky: 0xf2c979,
        fog: 0xffedbf,
        floor: ['#d8a24c', '#b98535', '#7c5b2f'],
        wall: 0x805f35,
        wallCap: 0x5f4328,
        wallBase: 0x4a321e,
        ceiling: 0xf4d99d,
        npc: 0x0f766e,
        npcAccent: 0x67e8f9,
    },
    castle: {
        sky: 0x9fb1c2,
        fog: 0xdfe6ee,
        floor: ['#7d8793', '#68727d', '#3d4652'],
        wall: 0x3d4652,
        wallCap: 0x28313b,
        wallBase: 0x1f2730,
        ceiling: 0xc8d2dc,
        npc: 0x7c3aed,
        npcAccent: 0xc4b5fd,
    },
    forest: {
        sky: 0x7fb391,
        fog: 0xd8f5e4,
        floor: ['#2f7f56', '#245c41', '#163d2d'],
        wall: 0x1f3f32,
        wallCap: 0x152c22,
        wallBase: 0x10231b,
        ceiling: 0xa9d8b8,
        npc: 0x0891b2,
        npcAccent: 0x67e8f9,
    },
    snow: {
        sky: 0xcde9f7,
        fog: 0xf4fbff,
        floor: ['#d8ebf8', '#bbd9eb', '#7f9db0'],
        wall: 0x7f9db0,
        wallCap: 0x607c8d,
        wallBase: 0x4c6474,
        ceiling: 0xf1f8fc,
        npc: 0x2563eb,
        npcAccent: 0xbfdbfe,
    },
};

const DEFAULT_MAZE = [
    [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
    [1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 1, 0, 1],
    [1, 0, 1, 0, 1, 0, 1, 1, 1, 0, 1, 0, 1],
    [1, 0, 1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 1],
    [1, 0, 1, 1, 1, 1, 1, 0, 1, 1, 1, 0, 1],
    [1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 1, 0, 1],
    [1, 1, 1, 1, 1, 0, 1, 1, 1, 0, 1, 0, 1],
    [1, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 1],
    [1, 0, 1, 0, 1, 1, 1, 0, 1, 1, 1, 0, 1],
    [1, 0, 1, 0, 0, 0, 1, 0, 0, 0, 1, 0, 1],
    [1, 0, 1, 1, 1, 0, 1, 1, 1, 0, 1, 0, 1],
    [1, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 1],
    [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
];

const DEFAULT_START_CELL = { col: 1, row: 1 };
const DEFAULT_GOAL_CELL = { col: 11, row: 11 };

class RpgBetaMaze {
    constructor(root) {
        this.root = root;
        this.keys = {
            forward: false,
            back: false,
            left: false,
            right: false,
            turnLeft: false,
            turnRight: false,
        };
        this.assets = {
            wall: null,
            arrow: null,
        };
        this.velocity = new THREE.Vector3();
        this.clock = new THREE.Clock();
        this.yaw = Math.PI / 2;
        this.steps = 0;
        this.goalReached = false;
        this.drag = { active: false, pointerId: null, lastX: 0 };
        this.mapConfig = this.readMapConfig();
        this.gridSize = this.mapConfig.gridSize;
        this.themeName = this.mapConfig.themeName;
        this.theme = THEME_PALETTES[this.themeName] || THEME_PALETTES.grass;
        this.maze = this.mapConfig.maze;
        this.startCell = this.mapConfig.startCell;
        this.goalCell = this.mapConfig.goalCell;
        this.npcPoints = this.mapConfig.npcPoints;
        this.enemyInitial = this.mapConfig.enemies;
        this.settings = this.mapConfig.settings;
        this.session = this.mapConfig.session;
        this.character = this.mapConfig.character;
        this.urls = this.mapConfig.urls;
        this.csrfToken = this.mapConfig.csrfToken;
        this.modeLabel = this.mapConfig.modeLabel;
        this.initialStatus = this.mapConfig.initialStatus;
        this.goalStatus = this.mapConfig.goalStatus;
        this.answeredNpcIds = new Set();
        this.score = 0;
        this.ammo = 0;
        this.shieldUntil = 0;
        this.currentNpc = null;
        this.answerResult = null;
        this.pickups = [];
        this.enemies = [];
        this.onlinePlayers = [];
        this.activePlayersCount = 1;
        this.enemyObjects = new Map();
        this.pickupObjects = new Map();
        this.otherPlayerObjects = new Map();
        this.lastAutoShotAt = 0;
        this.onlineSync = {
            polling: false,
            movePending: false,
            lastPollAt: 0,
            lastMoveAt: 0,
            lastSentCellKey: '',
        };
        this.orientation = {
            attempted: false,
            locking: false,
        };
        this.lastFootstepAt = 0;
        this.audio = {
            context: null,
            master: null,
            ambient: null,
            unlocked: false,
            failed: false,
        };
        this.prebuilt = {
            pickup: {},
            shot: null,
        };
        this.pools = {
            pickups: { shield: [], ammo: [] },
            players: [],
            shots: [],
        };
        this.performance = {
            basePixelRatio: Math.min(window.devicePixelRatio || 1, 2),
            currentPixelRatio: Math.min(window.devicePixelRatio || 1, 2),
            frames: 0,
            lastSampleAt: performance.now(),
        };
        this.uiHidden = this.readBooleanPreference('pkg-rpg-beta-ui-hidden', false);
        this.qualityMode = this.readQualityPreference();

        this.mount();
        this.applyQualityMode(true);
        this.initThree();
        this.bindEvents();
        this.buildScene();
        this.prewarmRuntimeAssets();
        this.resetPlayer();
        this.compileRuntimeAssets();
        this.loadModels();
        this.updateMinimap(true);
        this.animate();
    }

    mount() {
        this.root.innerHTML = `
            <div class="rpg-beta-canvas" data-beta-canvas></div>
            <div class="rpg-beta-panel">
                <div class="rpg-beta-stat">
                    <span>Mode</span>
                    <strong>${escapeHtml(this.modeLabel)}</strong>
                </div>
                <div class="rpg-beta-stat">
                    <span>Langkah</span>
                    <strong data-beta-steps>0</strong>
                </div>
                <div class="rpg-beta-stat">
                    <span>Skor</span>
                    <strong data-beta-score>0</strong>
                </div>
                <div class="rpg-beta-stat">
                    <span>NPC</span>
                    <strong data-beta-npcs>0/0</strong>
                </div>
                <div class="rpg-beta-stat">
                    <span>Peluru</span>
                    <strong data-beta-ammo>0</strong>
                </div>
                <div class="rpg-beta-stat">
                    <span>Tameng</span>
                    <strong data-beta-shield>OFF</strong>
                </div>
                <div class="rpg-beta-stat">
                    <span>Arah</span>
                    <strong data-beta-heading>Timur</strong>
                </div>
                <div class="rpg-beta-stat">
                    <span>Online</span>
                    <strong data-beta-online>1</strong>
                </div>
            </div>
            <div class="rpg-beta-actions">
                <button type="button" data-beta-action="ui-toggle" aria-pressed="true">
                    <span data-beta-ui-toggle-text>Panel</span>
                </button>
                <button type="button" data-beta-action="ui-close" title="Sembunyikan panel">X</button>
                <button type="button" data-beta-action="quality" title="Kualitas render">
                    <span data-beta-quality-label>Auto</span>
                </button>
                <button type="button" data-beta-action="shoot">Tembak</button>
                <button type="button" data-beta-action="reset">Reset</button>
                <button type="button" data-beta-action="fullscreen">Layar</button>
            </div>
            <div class="rpg-beta-status">
                <span>Status</span>
                <strong data-beta-status>${escapeHtml(this.initialStatus)}</strong>
            </div>
            <div class="rpg-beta-orientation-hint" data-beta-orientation-hint hidden>
                <strong>Putar layar</strong>
                <span>Mode 3D lebih stabil di landscape.</span>
            </div>
            <div class="rpg-beta-crosshair" aria-hidden="true"></div>
            <div class="rpg-beta-minimap" data-beta-minimap aria-label="Minimap beta 3D"></div>
            <div class="rpg-beta-shield-vignette" data-beta-shield-vignette hidden></div>
            <div class="rpg-beta-dialog" data-beta-dialog hidden></div>
            <div class="rpg-beta-controls" aria-label="Kontrol beta 3D">
                <div class="rpg-beta-pad rpg-beta-move-pad" aria-label="Gerak karakter">
                    <span></span>
                    <button type="button" data-beta-hold="forward" aria-label="Maju"><span aria-hidden="true">&uarr;</span></button>
                    <span></span>
                    <button type="button" data-beta-hold="left" aria-label="Geser kiri"><span aria-hidden="true">&larr;</span></button>
                    <span class="rpg-beta-pad-core" aria-hidden="true"></span>
                    <button type="button" data-beta-hold="right" aria-label="Geser kanan"><span aria-hidden="true">&rarr;</span></button>
                    <span></span>
                    <button type="button" data-beta-hold="back" aria-label="Mundur"><span aria-hidden="true">&darr;</span></button>
                    <span></span>
                </div>
                <div class="rpg-beta-pad rpg-beta-turn-pad" aria-label="Putar kamera">
                    <button type="button" data-beta-hold="turnLeft" aria-label="Putar kamera kiri"><span aria-hidden="true">&#8630;</span></button>
                    <button type="button" data-beta-hold="turnRight" aria-label="Putar kamera kanan"><span aria-hidden="true">&#8631;</span></button>
                </div>
            </div>
        `;

        this.canvasHost = this.root.querySelector('[data-beta-canvas]');
        this.stepsLabel = this.root.querySelector('[data-beta-steps]');
        this.scoreLabel = this.root.querySelector('[data-beta-score]');
        this.npcsLabel = this.root.querySelector('[data-beta-npcs]');
        this.ammoLabel = this.root.querySelector('[data-beta-ammo]');
        this.shieldLabel = this.root.querySelector('[data-beta-shield]');
        this.headingLabel = this.root.querySelector('[data-beta-heading]');
        this.onlineLabel = this.root.querySelector('[data-beta-online]');
        this.statusLabel = this.root.querySelector('[data-beta-status]');
        this.orientationHint = this.root.querySelector('[data-beta-orientation-hint]');
        this.uiToggleText = this.root.querySelector('[data-beta-ui-toggle-text]');
        this.uiToggleButton = this.root.querySelector('[data-beta-action="ui-toggle"]');
        this.qualityLabel = this.root.querySelector('[data-beta-quality-label]');
        this.minimap = this.root.querySelector('[data-beta-minimap]');
        this.dialogHost = this.root.querySelector('[data-beta-dialog]');
        this.shieldVignette = this.root.querySelector('[data-beta-shield-vignette]');
    }

    readMapConfig() {
        const fallback = {
            gridSize: DEFAULT_MAZE.length - 2,
            themeName: 'grass',
            maze: DEFAULT_MAZE.map((row) => row.slice()),
            startCell: { ...DEFAULT_START_CELL },
            goalCell: { ...DEFAULT_GOAL_CELL },
            npcPoints: [],
            enemies: [],
            settings: {
                difficulty: 'easy',
                shieldDurationSeconds: 8,
                ammoPerPickup: 3,
                shieldPickupsCount: 1,
                ammoPickupsCount: 2,
            },
            session: { pos_x: 0, pos_y: 0 },
            character: {
                nama: 'Pemain',
                avatar: '?',
                avatar_display: '?',
                warna: '#3B82F6',
            },
            urls: { state: '', move: '' },
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            modeLabel: 'Demo',
            initialStatus: 'Jelajahi labirin, temui NPC, dan hindari musuh.',
            goalStatus: 'Tujuan ditemukan. Demo siap dievaluasi sebelum masuk ke RPG inti.',
        };

        const sourceId = this.root.dataset.mapSource;
        const source = sourceId ? document.getElementById(sourceId) : null;
        if (!source?.textContent) {
            return fallback;
        }

        try {
            const payload = JSON.parse(source.textContent);
            if (!payload || !payload.grid_size) {
                return fallback;
            }

            return this.buildRpgMapConfig(payload);
        } catch (error) {
            console.error('RPG beta map payload gagal dibaca', error);
            return fallback;
        }
    }

    buildRpgMapConfig(payload) {
        const gridSize = clampInteger(payload.grid_size, 4, 24);
        const mazeSize = gridSize + 2;
        const maze = Array.from({ length: mazeSize }, (_, row) => (
            Array.from({ length: mazeSize }, (_, col) => (
                row === 0 || col === 0 || row === mazeSize - 1 || col === mazeSize - 1 ? 1 : 0
            ))
        ));

        (payload.obstacles || []).forEach((obstacle) => {
            const col = Number(obstacle?.x) + 1;
            const row = gridSize - Number(obstacle?.y);
            if (maze[row] && maze[row][col] === 0) {
                maze[row][col] = 1;
            }
        });

        const npcPoints = (payload.npcs || [])
            .map((npc) => ({
                id: npc.id,
                label: npc.nama || 'NPC',
                avatar: npc.avatar || 'NPC',
                col: Number(npc.pos_x) + 1,
                row: gridSize - Number(npc.pos_y),
                question: npc.pertanyaan || '',
                choices: Array.isArray(npc.pilihan_jawaban) ? npc.pilihan_jawaban : [],
                correctAnswerIndex: Number(npc.jawaban_benar ?? -1),
                points: Number(npc.poin || 0),
            }))
            .filter((point) => maze[point.row] && maze[point.row][point.col] === 0);

        const enemies = (payload.enemies || [])
            .map((enemy, index) => ({
                id: `enemy-${index}`,
                col: Number(enemy.x) + 1,
                row: gridSize - Number(enemy.y),
                spawnCol: Number(enemy.x) + 1,
                spawnRow: gridSize - Number(enemy.y),
                avatar: enemy.avatar || '!',
                speedLevel: ['slow', 'normal', 'fast'].includes(enemy.speed_level) ? enemy.speed_level : 'normal',
                intelligenceLevel: ['low', 'normal', 'high'].includes(enemy.intelligence_level) ? enemy.intelligence_level : 'normal',
                patrolAxis: index % 2 === 0 ? 'horizontal' : 'vertical',
                patrolDirection: index % 2 === 0 ? 1 : -1,
                alertRadius: 4 + (['low', 'normal', 'high'].indexOf(enemy.intelligence_level) + 1) * 2,
                nextMoveAt: 0,
            }))
            .filter((enemy) => maze[enemy.row] && maze[enemy.row][enemy.col] === 0);

        const startCell = this.findOpenCell(maze, { col: 1, row: gridSize }) || { col: 1, row: gridSize };
        const preferredGoal = npcPoints.length
            ? npcPoints
                .slice()
                .sort((a, b) => distance(startCell, b) - distance(startCell, a))[0]
            : { col: gridSize, row: 1 };
        const goalCell = this.findOpenCell(maze, preferredGoal) || startCell;
        const mapName = payload.nama || 'Map RPG';
        const character = payload.character || {};
        const urls = payload.urls || {};

        return {
            gridSize,
            themeName: THEME_PALETTES[payload.background_theme] ? payload.background_theme : 'grass',
            maze,
            startCell,
            goalCell,
            npcPoints,
            enemies,
            settings: {
                difficulty: payload.difficulty || 'easy',
                shieldDurationSeconds: clampInteger(payload.shield_duration_seconds ?? 8, 3, 60),
                ammoPerPickup: clampInteger(payload.ammo_per_pickup ?? 3, 1, 12),
                shieldPickupsCount: clampInteger(payload.shield_pickups_count ?? 1, 0, 8),
                ammoPickupsCount: clampInteger(payload.ammo_pickups_count ?? 2, 0, 12),
            },
            session: payload.session || { pos_x: 0, pos_y: 0 },
            character: {
                nama: character.nama || 'Pemain',
                avatar: character.avatar || character.avatar_display || '?',
                avatar_display: character.avatar_display || character.avatar || '?',
                warna: character.warna || '#3B82F6',
            },
            urls: {
                state: urls.state || '',
                move: urls.move || '',
            },
            csrfToken: payload.csrf_token || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            modeLabel: 'Map RPG',
            initialStatus: `${mapName}: jawab NPC, ambil pickup, dan hindari musuh.`,
            goalStatus: `${mapName}: semua sistem beta lokal siap diuji sebelum masuk ke RPG inti.`,
        };
    }

    findOpenCell(maze, preferred) {
        const candidates = [];
        maze.forEach((row, rowIndex) => {
            row.forEach((cell, colIndex) => {
                if (cell === 0) {
                    candidates.push({ col: colIndex, row: rowIndex });
                }
            });
        });

        return candidates
            .sort((a, b) => distance(preferred, a) - distance(preferred, b))[0] || null;
    }

    initThree() {
        this.renderer = new THREE.WebGLRenderer({ antialias: true, powerPreference: 'high-performance' });
        this.renderer.setPixelRatio(this.performance.currentPixelRatio);
        this.renderer.setClearColor(this.theme.sky, 1);
        this.renderer.outputColorSpace = THREE.SRGBColorSpace;
        this.renderer.domElement.style.touchAction = 'none';
        this.canvasHost.appendChild(this.renderer.domElement);

        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(this.theme.sky);
        this.scene.fog = new THREE.Fog(this.theme.fog, TILE_SIZE * 5, TILE_SIZE * 23);

        this.camera = new THREE.PerspectiveCamera(70, 1, 0.08, 180);
        this.camera.position.y = PLAYER_HEIGHT;

        const ambient = new THREE.HemisphereLight(0xffffff, this.theme.wallBase, 1.35);
        this.scene.add(ambient);

        const sun = new THREE.DirectionalLight(0xffffff, 1.1);
        sun.position.set(12, 18, 8);
        this.scene.add(sun);

        const rim = new THREE.DirectionalLight(this.theme.npcAccent, 0.28);
        rim.position.set(-9, 6, -12);
        this.scene.add(rim);

        const cameraLight = new THREE.PointLight(0xffffff, 0.8, TILE_SIZE * 4);
        this.camera.add(cameraLight);
        this.playerViewModel = this.makeFirstPersonHands();
        this.camera.add(this.playerViewModel);
        this.scene.add(this.camera);

        this.staticGroup = new THREE.Group();
        this.enemyGroup = new THREE.Group();
        this.pickupGroup = new THREE.Group();
        this.otherPlayerGroup = new THREE.Group();
        this.scene.add(this.staticGroup, this.enemyGroup, this.pickupGroup, this.otherPlayerGroup);

        this.resizeObserver = typeof ResizeObserver !== 'undefined'
            ? new ResizeObserver(() => this.resize())
            : null;
        this.resizeObserver?.observe(this.root);
        window.addEventListener('resize', () => this.resize());
        this.resize();
    }

    bindEvents() {
        const prepareImmersiveMobile = () => {
            this.unlockAudio();
            this.tryMobileLandscape(false);
        };
        this.root.addEventListener('pointerdown', prepareImmersiveMobile, { passive: true });
        this.root.addEventListener('click', prepareImmersiveMobile);
        window.addEventListener('keydown', prepareImmersiveMobile);
        window.addEventListener('orientationchange', () => window.setTimeout(() => this.updateOrientationHint(), 150));
        window.addEventListener('resize', () => this.updateOrientationHint());

        this.root.querySelectorAll('[data-beta-hold]').forEach((button) => {
            const action = button.getAttribute('data-beta-hold');
            const start = (event) => {
                event.preventDefault();
                this.keys[action] = true;
                try {
                    button.setPointerCapture(event.pointerId);
                } catch (error) {
                    // ignore unsupported capture
                }
            };
            const stop = (event) => {
                event?.preventDefault();
                this.keys[action] = false;
            };

            button.addEventListener('pointerdown', start);
            button.addEventListener('pointerup', stop);
            button.addEventListener('pointercancel', stop);
            button.addEventListener('pointerleave', stop);
        });

        this.root.querySelectorAll('[data-beta-action]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                const action = button.getAttribute('data-beta-action');
                if (action === 'ui-toggle') {
                    this.toggleUiPanel();
                } else if (action === 'ui-close') {
                    this.hideUiPanel();
                } else if (action === 'quality') {
                    this.cycleQualityMode();
                } else if (action === 'reset') {
                    this.resetPlayer();
                } else if (action === 'fullscreen') {
                    this.enterFullscreen();
                } else if (action === 'shoot') {
                    this.shootForward(true);
                }
            });
        });

        this.dialogHost?.addEventListener('click', (event) => {
            const answerButton = event.target.closest('[data-beta-answer]');
            const closeButton = event.target.closest('[data-beta-close-dialog]');
            if (!answerButton && !closeButton) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            if (answerButton) {
                this.submitNpcAnswer(Number(answerButton.getAttribute('data-beta-answer')));
            } else {
                this.closeNpcDialog();
            }
        });

        this.renderer.domElement.addEventListener('pointerdown', (event) => {
            if (event.pointerType === 'mouse' && event.button !== 0) {
                return;
            }

            this.drag.active = true;
            this.drag.pointerId = event.pointerId;
            this.drag.lastX = event.clientX;
            try {
                this.renderer.domElement.setPointerCapture(event.pointerId);
            } catch (error) {
                // ignore unsupported capture
            }
        });

        this.renderer.domElement.addEventListener('pointermove', (event) => {
            if (!this.drag.active || this.drag.pointerId !== event.pointerId) {
                return;
            }

            const movementX = event.clientX - this.drag.lastX;
            this.drag.lastX = event.clientX;
            this.yaw -= movementX * 0.004;
            this.updateHeading();
        });

        const endDrag = (event) => {
            if (this.drag.pointerId === event.pointerId) {
                this.drag.active = false;
                this.drag.pointerId = null;
            }
        };
        this.renderer.domElement.addEventListener('pointerup', endDrag);
        this.renderer.domElement.addEventListener('pointercancel', endDrag);

        window.addEventListener('keydown', (event) => this.setKey(event, true));
        window.addEventListener('keyup', (event) => this.setKey(event, false));
        this.updateUiState();
    }

    toggleUiPanel() {
        this.uiHidden = !this.uiHidden;
        if (this.uiHidden) {
            this.stopAllMovementInput();
        }
        this.updateUiState();
    }

    hideUiPanel() {
        this.uiHidden = true;
        this.stopAllMovementInput();
        this.updateUiState();
    }

    updateUiState() {
        this.root.classList.toggle('is-ui-hidden', this.uiHidden);
        window.localStorage?.setItem('pkg-rpg-beta-ui-hidden', this.uiHidden ? '1' : '0');
        if (this.uiToggleButton) {
            this.uiToggleButton.setAttribute('aria-pressed', this.uiHidden ? 'false' : 'true');
        }
        if (this.uiToggleText) {
            this.uiToggleText.textContent = this.uiHidden ? 'Panel' : 'Panel';
        }
    }

    stopAllMovementInput() {
        Object.keys(this.keys).forEach((key) => {
            this.keys[key] = false;
        });
        this.velocity.set(0, 0, 0);
    }

    cycleQualityMode() {
        const modes = ['auto', 'battery', 'high'];
        const currentIndex = modes.indexOf(this.qualityMode);
        this.qualityMode = modes[(currentIndex + 1 + modes.length) % modes.length];
        window.localStorage?.setItem('pkg-rpg-beta-quality', this.qualityMode);
        this.applyQualityMode();
    }

    applyQualityMode(initial = false) {
        const base = Math.min(window.devicePixelRatio || 1, 2);
        this.performance.basePixelRatio = base;
        let nextRatio = this.performance.currentPixelRatio;
        if (this.qualityMode === 'battery') {
            nextRatio = 1;
        } else if (this.qualityMode === 'high') {
            nextRatio = base;
        } else if (initial) {
            nextRatio = Math.min(base, 1.75);
        }

        this.performance.currentPixelRatio = nextRatio;
        if (this.renderer) {
            this.renderer.setPixelRatio(nextRatio);
            this.resize(true);
        }

        if (this.qualityLabel) {
            this.qualityLabel.textContent = {
                auto: 'Auto',
                battery: 'Hemat',
                high: 'Tinggi',
            }[this.qualityMode] || 'Auto';
        }
    }

    readQualityPreference() {
        const value = window.localStorage?.getItem('pkg-rpg-beta-quality');
        return ['auto', 'battery', 'high'].includes(value) ? value : 'auto';
    }

    readBooleanPreference(key, fallback = false) {
        const value = window.localStorage?.getItem(key);
        if (value === '1') {
            return true;
        }
        if (value === '0') {
            return false;
        }
        return fallback;
    }

    setKey(event, pressed) {
        const tag = event.target?.tagName;
        if (tag && ['INPUT', 'TEXTAREA', 'SELECT'].includes(tag)) {
            return;
        }

        const key = event.key.toLowerCase();
        const map = {
            w: 'forward',
            arrowup: 'forward',
            s: 'back',
            arrowdown: 'back',
            a: 'left',
            d: 'right',
            q: 'turnLeft',
            arrowleft: 'turnLeft',
            e: 'turnRight',
            arrowright: 'turnRight',
        };

        if ((event.code === 'Space' || key === 'enter') && pressed) {
            event.preventDefault();
            this.shootForward(true);
            return;
        }

        const action = map[key];
        if (!action) {
            return;
        }

        event.preventDefault();
        this.keys[action] = pressed;
    }

    buildScene() {
        this.clearGroup(this.staticGroup);

        this.materials = this.makeMaterials();
        const size = this.maze.length * TILE_SIZE;

        const floor = new THREE.Mesh(
            new THREE.PlaneGeometry(size, size),
            this.materials.floor,
        );
        floor.rotation.x = -Math.PI / 2;
        floor.position.y = -0.02;
        this.staticGroup.add(floor);

        const ceiling = new THREE.Mesh(
            new THREE.PlaneGeometry(size, size),
            this.materials.ceiling,
        );
        ceiling.rotation.x = Math.PI / 2;
        ceiling.position.y = 3.55;
        this.staticGroup.add(ceiling);

        const wallCells = [];
        this.maze.forEach((row, rowIndex) => {
            row.forEach((cell, colIndex) => {
                if (cell !== 1) {
                    return;
                }

                wallCells.push({ col: colIndex, row: rowIndex });
            });
        });
        this.addWallInstances(wallCells);
        this.addThemeDetails();

        this.goal = null;

        this.npcPoints.forEach((point) => {
            const marker = this.makeNpcPointMarker(point);
            point.marker = marker;
            const pos = this.cellToWorld(point.col, point.row);
            marker.position.set(pos.x, 0.02, pos.z);
            this.staticGroup.add(marker);
        });
        this.updateNpcMarkers();
    }

    addThemeDetails() {
        const occupied = new Set([
            `${this.startCell.col},${this.startCell.row}`,
            ...this.npcPoints.map((point) => `${point.col},${point.row}`),
            ...this.enemyInitial.map((enemy) => `${enemy.col},${enemy.row}`),
        ]);
        const candidates = this.openCells()
            .filter((cell) => !occupied.has(`${cell.col},${cell.row}`))
            .filter((cell) => cellNoise(cell.col, cell.row) > 0.62)
            .slice(0, Math.min(58, this.gridSize * 3));

        if (!candidates.length) {
            return;
        }

        const propColor = {
            grass: 0x2f7f56,
            forest: 0x14532d,
            desert: 0x9a6a34,
            castle: 0x475569,
            snow: 0xe0f2fe,
        }[this.themeName] || 0x2f7f56;
        const accentColor = {
            grass: 0x86efac,
            forest: 0x4ade80,
            desert: 0xfacc15,
            castle: 0x94a3b8,
            snow: 0xffffff,
        }[this.themeName] || 0x86efac;

        const propGeometry = ['grass', 'forest'].includes(this.themeName)
            ? new THREE.ConeGeometry(0.08, 0.42, 5)
            : new THREE.DodecahedronGeometry(0.16, 0);
        const propMaterial = new THREE.MeshLambertMaterial({ color: propColor, emissive: propColor, emissiveIntensity: 0.04 });
        const accentGeometry = ['grass', 'forest'].includes(this.themeName)
            ? new THREE.ConeGeometry(0.055, 0.34, 5)
            : new THREE.SphereGeometry(0.11, 10, 6);
        const accentMaterial = new THREE.MeshLambertMaterial({ color: accentColor, emissive: accentColor, emissiveIntensity: this.themeName === 'snow' ? 0.1 : 0.03 });
        const prop = new THREE.InstancedMesh(propGeometry, propMaterial, candidates.length);
        const accent = new THREE.InstancedMesh(accentGeometry, accentMaterial, candidates.length);
        const matrix = new THREE.Matrix4();
        const rotation = new THREE.Euler();
        const quaternion = new THREE.Quaternion();
        const scale = new THREE.Vector3();

        candidates.forEach((cell, index) => {
            const pos = this.cellToWorld(cell.col, cell.row);
            const noise = cellNoise(cell.row, cell.col);
            const offsetX = (noise - 0.5) * TILE_SIZE * 0.46;
            const offsetZ = (cellNoise(cell.col + 7, cell.row + 3) - 0.5) * TILE_SIZE * 0.46;
            rotation.set(0, noise * Math.PI * 2, 0);
            quaternion.setFromEuler(rotation);
            const size = 0.75 + noise * 0.55;
            scale.set(size, size, size);
            matrix.compose(new THREE.Vector3(pos.x + offsetX, 0.16, pos.z + offsetZ), quaternion, scale);
            prop.setMatrixAt(index, matrix);

            const accentScale = 0.65 + cellNoise(cell.col + 11, cell.row + 5) * 0.45;
            scale.set(accentScale, accentScale, accentScale);
            matrix.compose(new THREE.Vector3(pos.x - offsetX * 0.4, 0.2, pos.z - offsetZ * 0.35), quaternion, scale);
            accent.setMatrixAt(index, matrix);
        });

        prop.instanceMatrix.needsUpdate = true;
        accent.instanceMatrix.needsUpdate = true;
        this.staticGroup.add(prop, accent);
    }

    makeMaterials() {
        const floorTexture = makeGridTexture(this.theme.floor[0], this.theme.floor[1], this.theme.floor[2]);
        floorTexture.wrapS = THREE.RepeatWrapping;
        floorTexture.wrapT = THREE.RepeatWrapping;
        floorTexture.repeat.set(this.maze.length, this.maze.length);

        const wallTexture = makeBrickTexture(this.theme.wall, this.theme.wallCap, this.theme.wallBase);
        wallTexture.wrapS = THREE.RepeatWrapping;
        wallTexture.wrapT = THREE.RepeatWrapping;
        wallTexture.repeat.set(1.5, 1);

        return {
            floor: new THREE.MeshLambertMaterial({ map: floorTexture }),
            ceiling: new THREE.MeshLambertMaterial({ color: this.theme.ceiling }),
            wall: new THREE.MeshStandardMaterial({
                map: wallTexture,
                roughness: 0.86,
                metalness: 0.02,
            }),
            wallCap: new THREE.MeshLambertMaterial({ color: this.theme.wallCap }),
            wallBase: new THREE.MeshLambertMaterial({ color: this.theme.wallBase }),
            wallEdge: new THREE.LineBasicMaterial({ color: darkenHexColor(this.theme.wallBase, 0.72), transparent: true, opacity: 0.72 }),
            goal: new THREE.MeshLambertMaterial({
                color: 0x10b981,
                emissive: 0x10b981,
                emissiveIntensity: 0.34,
            }),
            enemy: new THREE.MeshLambertMaterial({ color: 0xdc2626, emissive: 0x7f1d1d, emissiveIntensity: 0.18 }),
            shieldPickup: new THREE.MeshLambertMaterial({ color: 0x10b981, emissive: 0x10b981, emissiveIntensity: 0.28 }),
            ammoPickup: new THREE.MeshLambertMaterial({ color: 0xf59e0b, emissive: 0xf59e0b, emissiveIntensity: 0.24 }),
        };
    }

    addWallInstances(wallCells) {
        if (!wallCells.length) {
            return;
        }

        const bodyGeometry = new THREE.BoxGeometry(TILE_SIZE, 3.4, TILE_SIZE);
        const capGeometry = new THREE.BoxGeometry(TILE_SIZE + 0.1, 0.14, TILE_SIZE + 0.1);
        const baseGeometry = new THREE.BoxGeometry(TILE_SIZE + 0.08, 0.18, TILE_SIZE + 0.08);
        const body = new THREE.InstancedMesh(bodyGeometry, this.materials.wall, wallCells.length);
        const cap = new THREE.InstancedMesh(capGeometry, this.materials.wallCap, wallCells.length);
        const base = new THREE.InstancedMesh(baseGeometry, this.materials.wallBase, wallCells.length);
        const matrix = new THREE.Matrix4();

        wallCells.forEach((cell, index) => {
            const pos = this.cellToWorld(cell.col, cell.row);
            matrix.makeTranslation(pos.x, 1.7, pos.z);
            body.setMatrixAt(index, matrix);
            matrix.makeTranslation(pos.x, 3.47, pos.z);
            cap.setMatrixAt(index, matrix);
            matrix.makeTranslation(pos.x, 0.09, pos.z);
            base.setMatrixAt(index, matrix);
        });

        body.instanceMatrix.needsUpdate = true;
        cap.instanceMatrix.needsUpdate = true;
        base.instanceMatrix.needsUpdate = true;
        body.castShadow = false;
        body.receiveShadow = true;
        cap.receiveShadow = true;
        base.receiveShadow = true;
        this.staticGroup.add(body, cap, base);
    }

    makeWall() {
        const group = new THREE.Group();
        const bodyGeometry = new THREE.BoxGeometry(TILE_SIZE, 3.4, TILE_SIZE);
        const wall = new THREE.Mesh(
            bodyGeometry,
            this.materials.wall,
        );
        const cap = new THREE.Mesh(
            new THREE.BoxGeometry(TILE_SIZE + 0.1, 0.14, TILE_SIZE + 0.1),
            this.materials.wallCap,
        );
        const base = new THREE.Mesh(
            new THREE.BoxGeometry(TILE_SIZE + 0.08, 0.18, TILE_SIZE + 0.08),
            this.materials.wallBase,
        );
        const edge = new THREE.LineSegments(
            new THREE.EdgesGeometry(bodyGeometry),
            this.materials.wallEdge,
        );

        cap.position.y = 1.76;
        base.position.y = -1.6;
        group.add(wall, cap, base, edge);

        return group;
    }

    makeNpcPointMarker(point = {}) {
        return this.makeHumanoidMarker({
            avatar: point.avatar || 'NPC',
            label: point.label || 'NPC',
            primary: this.theme.npc,
            secondary: this.theme.npcAccent,
            labelColor: this.theme.npc,
            opacity: 0.98,
        });
    }

    makeTextSprite(text, color = 0x1d4ed8, options = {}) {
        const canvas = document.createElement('canvas');
        canvas.width = 256;
        canvas.height = 96;
        const context = canvas.getContext('2d');
        const opacity = clampNumber(options.opacity ?? 1, 0.1, 1);
        context.clearRect(0, 0, canvas.width, canvas.height);
        context.fillStyle = `rgba(255, 255, 255, ${0.88 * opacity})`;
        context.strokeStyle = `rgba(15, 23, 42, ${0.24 * opacity})`;
        context.lineWidth = 4;
        roundRect(context, 18, 18, 220, 54, 18);
        context.fill();
        context.stroke();
        context.fillStyle = `#${Number(color || 0x1d4ed8).toString(16).padStart(6, '0')}`;
        context.font = '700 26px Inter, Arial, sans-serif';
        context.textAlign = 'center';
        context.textBaseline = 'middle';
        context.fillText(String(text || 'NPC').slice(0, 18), canvas.width / 2, 45);

        const texture = new THREE.CanvasTexture(canvas);
        const sprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: texture, transparent: true, opacity }));
        sprite.position.y = Number(options.y ?? 2.36);
        sprite.scale.set(Number(options.scaleX ?? 1.9), Number(options.scaleY ?? 0.72), 1);
        return sprite;
    }

    makeAvatarBadgeSprite(text, color = 0x2563eb, options = {}) {
        const canvas = document.createElement('canvas');
        canvas.width = 192;
        canvas.height = 192;
        const context = canvas.getContext('2d');
        const opacity = clampNumber(options.opacity ?? 1, 0.1, 1);
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

    makeHumanoidMarker(options = {}) {
        const group = new THREE.Group();
        const opacity = clampNumber(options.opacity ?? 1, 0.18, 1);
        const primary = Number(options.primary ?? 0x2563eb);
        const secondary = Number(options.secondary ?? mixHexColor(primary, 0xffffff, 0.42));
        const skin = Number(options.skin ?? 0xf8c9a4);
        const material = (color, emissive = 0x000000, emissiveIntensity = 0.03) => new THREE.MeshStandardMaterial({
            color,
            emissive,
            emissiveIntensity,
            roughness: 0.66,
            metalness: 0.02,
            transparent: opacity < 1,
            opacity,
            depthWrite: opacity >= 0.65,
        });

        const shadow = new THREE.Mesh(
            new THREE.CylinderGeometry(0.62, 0.62, 0.025, 36),
            new THREE.MeshBasicMaterial({
                color: 0x020617,
                transparent: true,
                opacity: 0.16 * opacity,
                depthWrite: false,
            }),
        );
        shadow.scale.z = 0.72;
        shadow.position.y = 0.02;

        const torso = new THREE.Mesh(new THREE.BoxGeometry(0.72, 0.9, 0.44), material(primary, primary, 0.05));
        torso.position.y = 1.02;

        const chest = new THREE.Mesh(new THREE.BoxGeometry(0.5, 0.34, 0.06), material(secondary));
        chest.position.set(0, 1.16, -0.26);

        const head = new THREE.Mesh(new THREE.SphereGeometry(0.34, 24, 16), material(skin));
        head.position.y = 1.72;
        head.scale.set(1, 1.08, 0.96);

        const armGeometry = new THREE.CapsuleGeometry(0.13, 0.58, 6, 14);
        const legGeometry = new THREE.CapsuleGeometry(0.15, 0.62, 6, 14);
        const leftArm = new THREE.Mesh(armGeometry, material(secondary));
        const rightArm = new THREE.Mesh(armGeometry, material(secondary));
        const leftLeg = new THREE.Mesh(legGeometry, material(primary));
        const rightLeg = new THREE.Mesh(legGeometry, material(primary));

        leftArm.position.set(-0.52, 1.04, 0);
        rightArm.position.set(0.52, 1.04, 0);
        leftLeg.position.set(-0.21, 0.42, 0);
        rightLeg.position.set(0.21, 0.42, 0);

        group.add(
            shadow,
            torso,
            chest,
            head,
            leftArm,
            rightArm,
            leftLeg,
            rightLeg,
            this.makeAvatarBadgeSprite(options.avatar || '?', primary, { opacity, y: 1.74, scale: 0.58 }),
            this.makeTextSprite(options.label || 'NPC', options.labelColor || primary, { opacity, y: 2.35 }),
        );

        if (options.glow) {
            const light = new THREE.PointLight(Number(options.glow), 0.45 * opacity, TILE_SIZE * 1.9);
            light.position.y = 1.25;
            group.add(light);
        }

        group.userData.humanoidParts = { leftArm, rightArm, leftLeg, rightLeg, head, torso };
        group.userData.walkPhase = Math.random() * Math.PI * 2;
        group.userData.baseOpacity = opacity;
        return group;
    }

    makeGoal() {
        const group = new THREE.Group();
        const ring = new THREE.Mesh(
            new THREE.TorusGeometry(1.05, 0.12, 16, 64),
            this.materials.goal,
        );
        ring.rotation.x = Math.PI / 2;
        ring.position.y = 0.08;

        const beacon = new THREE.Mesh(
            new THREE.ConeGeometry(0.7, 1.8, 4),
            this.materials.goal,
        );
        beacon.position.y = 1.1;
        beacon.rotation.y = Math.PI / 4;

        group.add(ring, beacon, new THREE.PointLight(0x10b981, 1.4, TILE_SIZE * 4));
        return group;
    }

    makeEnemyMarker(enemy = {}) {
        const group = this.makeHumanoidMarker({
            avatar: enemy.avatar || '!',
            label: 'Musuh',
            primary: 0xdc2626,
            secondary: 0xf97316,
            labelColor: 0x991b1b,
            opacity: 0.98,
        });
        const alertRing = new THREE.Mesh(
            new THREE.TorusGeometry(0.74, 0.035, 10, 48),
            new THREE.MeshBasicMaterial({
                color: 0xef4444,
                transparent: true,
                opacity: 0.58,
                depthWrite: false,
            }),
        );
        alertRing.rotation.x = Math.PI / 2;
        alertRing.position.y = 0.06;
        alertRing.visible = false;
        group.add(alertRing);
        group.userData.alertRing = alertRing;
        return group;
    }

    makeOtherPlayerMarker(player = {}) {
        const color = parseHexColor(player.warna, 0x38bdf8);
        const group = this.makeHumanoidMarker({
            avatar: player.avatar_display || player.avatar || '?',
            label: player.nama || 'Pemain',
            primary: color,
            secondary: mixHexColor(color, 0xffffff, 0.46),
            labelColor: color,
            opacity: 0.42,
        });
        group.userData.avatarKey = this.otherPlayerAvatarKey(player);
        return group;
    }

    makeFirstPersonHands() {
        const group = new THREE.Group();
        const color = parseHexColor(this.character?.warna, 0x3b82f6);
        const accent = mixHexColor(color, 0xffffff, 0.36);
        const material = new THREE.MeshStandardMaterial({
            color: accent,
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
            const sleeve = new THREE.Mesh(new THREE.BoxGeometry(0.18, 0.18, 0.48), material);
            const hand = new THREE.Mesh(new THREE.BoxGeometry(0.19, 0.16, 0.18), handMaterial);
            sleeve.position.z = -0.1;
            hand.position.z = -0.38;
            arm.add(sleeve, hand);
            arm.position.set(side * 0.42, -0.52, -0.78);
            arm.rotation.set(-0.34, side * 0.18, side * 0.08);
            return arm;
        };

        group.add(makeArm(-1), makeArm(1));
        const weapon = this.makeWeaponModel({ firstPerson: true });
        weapon.position.set(0.38, -0.43, -1.12);
        weapon.rotation.set(-0.12, -0.08, 0.02);
        weapon.visible = false;
        weapon.userData.basePosition = weapon.position.clone();
        weapon.userData.baseRotation = weapon.rotation.clone();

        const shield = this.makeViewShieldModel();
        shield.position.set(-0.48, -0.36, -0.92);
        shield.rotation.set(0.12, 0.28, -0.2);
        shield.visible = false;

        group.add(weapon, shield);
        group.userData.weapon = weapon;
        group.userData.shield = shield;
        group.position.y = 0;
        return group;
    }

    makeWeaponModel(options = {}) {
        const group = new THREE.Group();
        const scale = options.firstPerson ? 1 : 0.78;
        const metal = new THREE.MeshStandardMaterial({
            color: 0x1f2937,
            roughness: 0.42,
            metalness: 0.34,
        });
        const grip = new THREE.MeshStandardMaterial({
            color: 0x78350f,
            roughness: 0.72,
            metalness: 0.04,
        });
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
        const triggerGuard = new THREE.Mesh(new THREE.TorusGeometry(0.13, 0.018, 8, 24, Math.PI * 1.4), metal);
        triggerGuard.rotation.set(Math.PI / 2, 0, Math.PI * 0.08);
        triggerGuard.position.set(0, -0.18, -0.06);
        const sight = new THREE.Mesh(new THREE.BoxGeometry(0.09, 0.06, 0.16), accent);
        sight.position.set(0, 0.2, -0.18);

        group.add(barrel, muzzle, body, handle, triggerGuard, sight);
        group.scale.setScalar(scale);
        return group;
    }

    makeViewShieldModel() {
        const shield = this.makeShieldMesh({
            color: 0x10b981,
            accent: 0x7dd3fc,
            opacity: 0.64,
            glow: true,
        });
        shield.scale.setScalar(0.52);
        return shield;
    }

    makeShieldMesh(options = {}) {
        const group = new THREE.Group();
        const color = Number(options.color ?? 0x10b981);
        const accent = Number(options.accent ?? 0xf8fafc);
        const opacity = clampNumber(options.opacity ?? 1, 0.2, 1);
        const shieldShape = new THREE.Shape();
        shieldShape.moveTo(0, 0.72);
        shieldShape.bezierCurveTo(0.46, 0.62, 0.64, 0.28, 0.54, -0.22);
        shieldShape.bezierCurveTo(0.44, -0.66, 0.16, -0.92, 0, -1.02);
        shieldShape.bezierCurveTo(-0.16, -0.92, -0.44, -0.66, -0.54, -0.22);
        shieldShape.bezierCurveTo(-0.64, 0.28, -0.46, 0.62, 0, 0.72);

        const material = new THREE.MeshStandardMaterial({
            color,
            roughness: 0.46,
            metalness: 0.16,
            transparent: opacity < 1,
            opacity,
            depthWrite: opacity >= 0.65,
            emissive: color,
            emissiveIntensity: options.glow ? 0.12 : 0.02,
        });
        const rimMaterial = new THREE.MeshStandardMaterial({
            color: accent,
            roughness: 0.35,
            metalness: 0.28,
            transparent: opacity < 1,
            opacity,
            depthWrite: opacity >= 0.65,
        });

        const face = new THREE.Mesh(
            new THREE.ExtrudeGeometry(shieldShape, { depth: 0.08, bevelEnabled: true, bevelSize: 0.025, bevelThickness: 0.02, bevelSegments: 2 }),
            material,
        );
        face.rotation.y = Math.PI;
        face.position.z = 0.04;

        const rim = new THREE.Mesh(new THREE.TorusGeometry(0.48, 0.035, 10, 48), rimMaterial);
        rim.scale.set(1.06, 1.36, 0.08);
        rim.position.y = -0.04;
        rim.position.z = -0.02;

        const boss = new THREE.Mesh(new THREE.SphereGeometry(0.18, 24, 12), rimMaterial);
        boss.scale.z = 0.34;
        boss.position.z = -0.08;

        const stripe = new THREE.Mesh(new THREE.BoxGeometry(0.08, 1.24, 0.055), rimMaterial);
        stripe.position.z = -0.1;
        stripe.rotation.z = -0.22;

        group.add(face, rim, boss, stripe);
        return group;
    }

    makePickupMarker(type) {
        if (!this.prebuilt.pickup[type]) {
            this.prebuilt.pickup[type] = this.createPickupPrototype(type);
        }
        const prototype = this.prebuilt.pickup[type];
        const group = prototype.clone(true);
        group.traverse((child) => {
            if (child.material) {
                child.material = child.material.clone();
            }
        });
        group.userData.pickupType = type;
        return group;
    }

    createPickupPrototype(type) {
        return type === 'shield' ? this.makeShieldPickupModel() : this.makeAmmoPickupModel();
    }

    makeShieldPickupModel() {
        const group = new THREE.Group();
        const shield = this.makeShieldMesh({
            color: 0x059669,
            accent: 0xdbeafe,
            glow: true,
        });
        shield.position.y = 1.04;
        shield.rotation.x = -0.08;
        shield.scale.setScalar(0.82);
        const base = new THREE.Mesh(
            new THREE.CylinderGeometry(0.48, 0.62, 0.16, 32),
            new THREE.MeshStandardMaterial({ color: 0x064e3b, roughness: 0.64, metalness: 0.06 }),
        );
        base.position.y = 0.08;
        group.add(base, shield);
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
        group.add(plate, weapon);
        return group;
    }

    syncEnemyObjects() {
        if (!this.enemyGroup) {
            return;
        }

        const live = new Set();
        const now = performance.now();
        this.enemies.forEach((enemy) => {
            live.add(enemy.id);
            let object = this.enemyObjects.get(enemy.id);
            if (!object) {
                object = this.makeEnemyMarker(enemy);
                this.enemyObjects.set(enemy.id, object);
                this.enemyGroup.add(object);
                const spawn = this.cellToWorld(enemy.col, enemy.row);
                enemy.visualX = Number.isFinite(enemy.visualX) ? enemy.visualX : spawn.x;
                enemy.visualZ = Number.isFinite(enemy.visualZ) ? enemy.visualZ : spawn.z;
                object.position.x = enemy.visualX;
                object.position.z = enemy.visualZ;
            }

            const pos = this.cellToWorld(enemy.col, enemy.row);
            if (!Number.isFinite(enemy.visualX) || !Number.isFinite(enemy.visualZ)) {
                enemy.visualX = pos.x;
                enemy.visualZ = pos.z;
            }
            if (object.userData.targetX !== pos.x || object.userData.targetZ !== pos.z) {
                object.userData.fromX = object.position.x;
                object.userData.fromZ = object.position.z;
                object.userData.moveStartedAt = now;
                object.userData.moveDuration = Math.max(520, Math.min(this.enemyMoveInterval(enemy) * 0.84, 1800));
            }
            object.userData.targetX = pos.x;
            object.userData.targetZ = pos.z;
            object.userData.enemyId = enemy.id;
            object.userData.alerted = !!enemy.alerted;
            if (object.userData.alertRing) {
                object.userData.alertRing.visible = !!enemy.alerted;
            }
            object.visible = !enemy.disabledUntil || performance.now() >= enemy.disabledUntil;
        });

        Array.from(this.enemyObjects.entries()).forEach(([key, object]) => {
            if (!live.has(key)) {
                this.enemyGroup.remove(object);
                this.disposeObject(object);
                this.enemyObjects.delete(key);
            }
        });
    }

    syncOtherPlayerObjects() {
        if (!this.otherPlayerGroup) {
            return;
        }

        const live = new Set();
        const now = performance.now();
        this.onlinePlayers.forEach((player, index) => {
            const cell = this.mapToMazeCell(player.pos_x, player.pos_y);
            if (!cell || !this.maze[cell.row] || this.maze[cell.row][cell.col] !== 0) {
                return;
            }

            const id = `player-${player.siswa_id || player.id || index}`;
            live.add(id);
            let object = this.otherPlayerObjects.get(id);
            if (!object) {
                object = this.acquireOtherPlayerObject(player);
                this.otherPlayerObjects.set(id, object);
                this.otherPlayerGroup.add(object);
                const spawn = this.cellToWorld(cell.col, cell.row);
                object.position.x = spawn.x;
                object.position.z = spawn.z;
            }

            const pos = this.cellToWorld(cell.col, cell.row);
            if (object.userData.targetX !== pos.x || object.userData.targetZ !== pos.z) {
                object.userData.fromX = object.position.x;
                object.userData.fromZ = object.position.z;
                object.userData.moveStartedAt = now;
                object.userData.moveDuration = 620;
            }
            object.userData.targetX = pos.x;
            object.userData.targetZ = pos.z;
            object.userData.playerId = id;
            object.visible = true;
        });

        Array.from(this.otherPlayerObjects.entries()).forEach(([key, object]) => {
            if (!live.has(key)) {
                this.otherPlayerGroup.remove(object);
                this.releaseOtherPlayerObject(object);
                this.otherPlayerObjects.delete(key);
            }
        });
    }

    acquireOtherPlayerObject(player) {
        const avatarKey = this.otherPlayerAvatarKey(player);
        const pooledIndex = this.pools.players.findIndex((item) => item.userData.avatarKey === avatarKey);
        const object = pooledIndex >= 0
            ? this.pools.players.splice(pooledIndex, 1)[0]
            : this.makeOtherPlayerMarker(player);
        object.visible = true;
        object.userData.playerName = player.nama || 'Pemain';
        object.userData.targetX = undefined;
        object.userData.targetZ = undefined;
        return object;
    }

    otherPlayerAvatarKey(player) {
        return [
            player.avatar_display || player.avatar || '?',
            player.nama || 'Pemain',
            player.warna || '#38bdf8',
        ].join(':');
    }

    releaseOtherPlayerObject(object) {
        object.visible = false;
        object.position.set(0, -100, 0);
        if (this.pools.players.length < 16) {
            this.pools.players.push(object);
        } else {
            this.disposeObject(object);
        }
    }

    syncPickupObjects() {
        if (!this.pickupGroup) {
            return;
        }

        const live = new Set();
        this.pickups.forEach((pickup) => {
            live.add(pickup.id);
            let object = this.pickupObjects.get(pickup.id);
            if (!object) {
                object = this.acquirePickupObject(pickup.type);
                this.pickupObjects.set(pickup.id, object);
                this.pickupGroup.add(object);
            }

            const pos = this.cellToWorld(pickup.col, pickup.row);
            object.position.x = pos.x;
            object.position.z = pos.z;
            object.visible = true;
        });

        Array.from(this.pickupObjects.entries()).forEach(([key, object]) => {
            if (!live.has(key)) {
                this.pickupGroup.remove(object);
                this.releasePickupObject(object);
                this.pickupObjects.delete(key);
            }
        });
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

    animateDynamicObjects(delta) {
        const elapsed = this.clock.elapsedTime;
        const moveLerp = 1 - Math.exp(-DYNAMIC_VISUAL_LERP * delta);
        this.enemyGroup?.children.forEach((object, index) => {
            const moved = this.advanceDynamicObject(object, moveLerp);
            const enemy = this.enemies.find((item) => item.id === object.userData.enemyId);
            if (enemy) {
                enemy.visualX = object.position.x;
                enemy.visualZ = object.position.z;
            }
            this.animateHumanoidObject(object, delta, elapsed, moved, index);
        });
        this.otherPlayerGroup?.children.forEach((object, index) => {
            const moved = this.advanceDynamicObject(object, moveLerp);
            this.animateHumanoidObject(object, delta, elapsed, moved, index + 20);
        });
        this.pickupGroup?.children.forEach((object, index) => {
            object.rotation.y += delta * 2.4;
            object.position.y = Math.sin(elapsed * 3.2 + index) * 0.08;
        });
        if (this.playerViewModel) {
            const speed = this.velocity.length();
            this.playerViewModel.position.y = Math.sin(this.steps * 5.8) * Math.min(0.035, speed * 0.006);
            const weapon = this.playerViewModel.userData.weapon;
            if (weapon?.userData.basePosition && weapon.userData.baseRotation) {
                const startedAt = Number(weapon.userData.kickStartedAt || 0);
                const progress = startedAt ? clampNumber((performance.now() - startedAt) / 180, 0, 1) : 1;
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

    kickWeapon() {
        const weapon = this.playerViewModel?.userData?.weapon;
        if (weapon) {
            weapon.userData.kickStartedAt = performance.now();
        }
    }

    flashShot(direction, range = AUTO_SHOOT_RANGE) {
        const line = this.prebuilt.shotLine;
        const geometry = this.prebuilt.shotGeometry;
        if (!line || !geometry || !this.player) {
            return;
        }

        const length = Math.max(1, Number(range || AUTO_SHOOT_RANGE)) * TILE_SIZE;
        const positions = geometry.getAttribute('position');
        positions.setXYZ(0, this.player.x, PLAYER_HEIGHT - 0.18, this.player.z);
        positions.setXYZ(1, this.player.x + direction.col * length, PLAYER_HEIGHT - 0.18, this.player.z + direction.row * length);
        positions.needsUpdate = true;
        geometry.computeBoundingSphere();
        line.visible = true;
        window.clearTimeout(line.userData.hideTimer);
        line.userData.hideTimer = window.setTimeout(() => {
            line.visible = false;
        }, 95);
    }

    advanceDynamicObject(object, fallbackLerp) {
        if (!Number.isFinite(object.userData.targetX) || !Number.isFinite(object.userData.targetZ)) {
            return false;
        }

        const beforeX = object.position.x;
        const beforeZ = object.position.z;
        const start = Number(object.userData.moveStartedAt || 0);
        const duration = Number(object.userData.moveDuration || 0);

        if (start > 0 && duration > 0) {
            const progress = clampNumber((performance.now() - start) / duration, 0, 1);
            const eased = progress * progress * (3 - (2 * progress));
            object.position.x = Number(object.userData.fromX ?? object.position.x)
                + (object.userData.targetX - Number(object.userData.fromX ?? object.position.x)) * eased;
            object.position.z = Number(object.userData.fromZ ?? object.position.z)
                + (object.userData.targetZ - Number(object.userData.fromZ ?? object.position.z)) * eased;
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

    animateHumanoidObject(object, delta, elapsed, moving, index = 0) {
        const parts = object.userData.humanoidParts;
        if (!parts) {
            return;
        }

        const alertBoost = object.userData.alerted ? 1.22 : 1;
        object.userData.walkPhase = Number(object.userData.walkPhase || 0) + delta * (moving ? 8.4 * alertBoost : 1.8);
        const phase = object.userData.walkPhase + index * 0.2;
        const stride = moving ? Math.sin(phase) : Math.sin(elapsed * 1.7 + index) * 0.08;
        const armSwing = moving ? stride * 0.46 : stride * 0.12;
        const legSwing = moving ? stride * 0.42 : stride * 0.08;

        parts.leftArm.rotation.x = armSwing;
        parts.rightArm.rotation.x = -armSwing;
        parts.leftLeg.rotation.x = -legSwing;
        parts.rightLeg.rotation.x = legSwing;
        parts.head.position.y = 1.72 + Math.sin(phase * 2) * (moving ? 0.025 : 0.012);
        object.position.y = Math.sin(elapsed * 2.1 + index) * (moving ? 0.03 * alertBoost : 0.018);
        if (object.userData.alertRing?.visible) {
            const pulse = 1 + Math.sin(elapsed * 5.4 + index) * 0.08;
            object.userData.alertRing.scale.set(pulse, pulse, 1);
            object.userData.alertRing.rotation.z += delta * 1.6;
        }
    }

    prewarmRuntimeAssets() {
        this.prebuilt.pickup.shield = this.createPickupPrototype('shield');
        this.prebuilt.pickup.ammo = this.createPickupPrototype('ammo');
        this.prebuilt.shotMaterial = new THREE.LineBasicMaterial({
            color: 0xfbbf24,
            transparent: true,
            opacity: 0.92,
        });
        this.prebuilt.shotGeometry = new THREE.BufferGeometry().setFromPoints([
            new THREE.Vector3(0, 0, 0),
            new THREE.Vector3(0, 0, -TILE_SIZE * 3),
        ]);
        this.prebuilt.shotLine = new THREE.Line(this.prebuilt.shotGeometry, this.prebuilt.shotMaterial);
        this.prebuilt.shotLine.visible = false;
        this.scene.add(this.prebuilt.shotLine);
        this.setupAudio();
    }

    compileRuntimeAssets() {
        const temporaryVisible = [
            this.playerViewModel?.userData?.weapon,
            this.playerViewModel?.userData?.shield,
            this.prebuilt.shotLine,
        ].filter(Boolean);
        const previousVisibility = temporaryVisible.map((object) => object.visible);
        temporaryVisible.forEach((object) => {
            object.visible = true;
        });

        try {
            this.renderer.compile(this.scene, this.camera);
        } catch (error) {
            // Rendering still works if a browser skips shader precompilation.
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
        } else if (name === 'hit') {
            this.playTone(220, 0.09, 0.07, 'square');
            window.setTimeout(() => this.playTone(140, 0.12, 0.05, 'triangle'), 60);
        } else if (name === 'miss') {
            this.playTone(190, 0.06, 0.035, 'triangle');
        } else if (name === 'shield-hit') {
            this.playNoise(0.18, 0.08, 900);
            this.playTone(360, 0.22, 0.045, 'sine');
        } else if (name === 'caught') {
            this.playTone(110, 0.22, 0.08, 'sawtooth');
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

    loadModels() {
        const loader = new GLTFLoader();
        const arrowUrl = this.root.dataset.arrowModel;

        if (arrowUrl) {
            loader.load(arrowUrl, (gltf) => {
                this.assets.arrow = gltf.scene;
                this.placeArrowModel();
            }, undefined, () => null);
        }
    }

    placeArrowModel() {
        if (!this.assets.arrow || !this.goal) {
            return;
        }

        const arrow = this.assets.arrow.clone(true);
        arrow.scale.setScalar(0.9);
        arrow.position.y = 2.2;
        arrow.rotation.y = Math.PI;
        this.goal.add(arrow);
    }

    resetPlayer() {
        const pos = this.cellToWorld(this.startCell.col, this.startCell.row);
        this.player = new THREE.Vector3(pos.x, PLAYER_HEIGHT, pos.z);
        this.velocity.set(0, 0, 0);
        this.yaw = Math.PI / 2;
        this.steps = 0;
        this.goalReached = false;
        this.score = 0;
        this.ammo = 0;
        this.shieldUntil = 0;
        this.currentNpc = null;
        this.answerResult = null;
        this.answeredNpcIds = new Set();
        this.enemies = this.enemyInitial.map((enemy) => {
            const pos = this.cellToWorld(enemy.col, enemy.row);
            return {
                ...enemy,
                visualX: pos.x,
                visualZ: pos.z,
                lastCol: enemy.col,
                lastRow: enemy.row,
                nextMoveAt: performance.now() + this.enemyMoveInterval(enemy),
            };
        });
        this.pickups = this.generatePickups();
        this.syncEnemyObjects();
        this.syncPickupObjects();
        this.updateNpcMarkers();
        this.updateDialog();
        this.statusLabel.textContent = this.initialStatus;
        this.updateHeading();
        this.updateHud();
        this.updateCamera();
        this.updateMinimap(true);
        this.syncCurrentCell(true);
        this.syncOnlineState(true);
        this.updateOrientationHint();
    }

    animate() {
        const delta = Math.min(this.clock.getDelta(), 0.06);
        this.update(delta);
        this.renderer.render(this.scene, this.camera);
        this.updateAdaptiveQuality();
        window.requestAnimationFrame(() => this.animate());
    }

    updateAdaptiveQuality() {
        if (this.qualityMode !== 'auto') {
            return;
        }

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
            this.resize(true);
        }
    }

    update(delta) {
        this.updateShieldState();
        this.syncOnlineState(false);

        if (this.currentNpc) {
            this.updateCamera();
            this.animateDynamicObjects(delta);
            return;
        }

        const turn = (this.keys.turnRight ? 1 : 0) - (this.keys.turnLeft ? 1 : 0);
        if (turn !== 0) {
            this.yaw -= turn * TURN_SPEED * delta;
            this.updateHeading();
        }

        const forward = (this.keys.forward ? 1 : 0) - (this.keys.back ? 1 : 0);
        const strafe = (this.keys.right ? 1 : 0) - (this.keys.left ? 1 : 0);
        const targetVelocity = new THREE.Vector3();

        if (forward !== 0 || strafe !== 0) {
            const sin = Math.sin(this.yaw);
            const cos = Math.cos(this.yaw);
            targetVelocity.x = (sin * forward - cos * strafe) * MOVE_SPEED;
            targetVelocity.z = (cos * forward + sin * strafe) * MOVE_SPEED;
            if (targetVelocity.length() > MOVE_SPEED) {
                targetVelocity.setLength(MOVE_SPEED);
            }
        }

        const lerpAmount = 1 - Math.exp(-DAMPING * delta);
        this.velocity.lerp(targetVelocity, lerpAmount);

        if (this.velocity.lengthSq() > 0.0004) {
            this.moveWithCollision(this.velocity.x * delta, this.velocity.z * delta);
            this.playFootstepIfNeeded();
        }

        this.updateCamera();
        this.updateGoal(delta);
        this.updateEnemies();
        this.checkEnemyCatch();
        this.tryAutoShoot();
        this.animateDynamicObjects(delta);
    }

    moveWithCollision(dx, dz) {
        let moved = false;
        const nextX = this.player.x + dx;
        if (!this.isBlocked(nextX, this.player.z)) {
            this.player.x = nextX;
            moved = moved || Math.abs(dx) > 0.0001;
        } else {
            this.velocity.x = 0;
        }

        const nextZ = this.player.z + dz;
        if (!this.isBlocked(this.player.x, nextZ)) {
            this.player.z = nextZ;
            moved = moved || Math.abs(dz) > 0.0001;
        } else {
            this.velocity.z = 0;
        }

        if (moved) {
            this.steps += Math.hypot(dx, dz);
            this.collectPickupAtPlayer();
            this.checkNpcEncounter();
            this.updateHud();
            this.updateMinimap(false);
            this.syncCurrentCell(false);
        }
    }

    syncOnlineState(force = false) {
        const stateUrl = this.urls?.state;
        if (!stateUrl || this.onlineSync.polling) {
            return;
        }

        const now = performance.now();
        if (!force && now - this.onlineSync.lastPollAt < ONLINE_STATE_POLL_MS) {
            return;
        }

        this.onlineSync.polling = true;
        this.onlineSync.lastPollAt = now;
        fetch(stateUrl, {
            headers: {
                Accept: 'application/json',
            },
        })
            .then((response) => (response.ok ? response.json() : null))
            .then((data) => {
                if (!data?.success) {
                    return;
                }

                this.onlinePlayers = Array.isArray(data.online_players) ? data.online_players : [];
                this.activePlayersCount = Number(data.active_players_count || (this.onlinePlayers.length + 1));
                this.syncOtherPlayerObjects();
                this.updateHud();
                this.updateMinimap(true);
            })
            .catch(() => null)
            .finally(() => {
                this.onlineSync.polling = false;
            });
    }

    syncCurrentCell(force = false) {
        const moveUrl = this.urls?.move;
        if (!moveUrl || !this.player || this.onlineSync.movePending) {
            return;
        }

        const now = performance.now();
        if (!force && now - this.onlineSync.lastMoveAt < ONLINE_MOVE_SYNC_MS) {
            return;
        }

        const cell = this.worldToMapCell(this.player.x, this.player.z);
        if (!cell) {
            return;
        }

        const key = `${cell.x},${cell.y}`;
        if (!force && key === this.onlineSync.lastSentCellKey) {
            return;
        }

        this.onlineSync.movePending = true;
        this.onlineSync.lastMoveAt = now;
        this.onlineSync.lastSentCellKey = key;

        fetch(moveUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken || '',
            },
            body: JSON.stringify({
                pos_x: cell.x,
                pos_y: cell.y,
            }),
        })
            .catch(() => null)
            .finally(() => {
                this.onlineSync.movePending = false;
            });
    }

    checkNpcEncounter() {
        const cell = this.worldToCell(this.player.x, this.player.z);
        const npc = this.npcPoints.find((point) => (
            point.col === cell.col
            && point.row === cell.row
            && !this.answeredNpcIds.has(Number(point.id))
        ));

        if (npc) {
            this.openNpcDialog(npc);
        }
    }

    openNpcDialog(npc) {
        this.currentNpc = npc;
        this.answerResult = null;
        this.velocity.set(0, 0, 0);
        this.keys.forward = false;
        this.keys.back = false;
        this.keys.left = false;
        this.keys.right = false;
        this.statusLabel.textContent = `Pertanyaan ${npc.label || 'NPC'} terbuka.`;
        this.updateDialog();
        this.updateOrientationHint();
    }

    submitNpcAnswer(index) {
        if (!this.currentNpc || this.answerResult) {
            return;
        }

        const correct = Number(index) === Number(this.currentNpc.correctAnswerIndex);
        const points = correct ? Number(this.currentNpc.points || 0) : 0;
        this.answerResult = { correct, points };

        if (correct) {
            this.answeredNpcIds.add(Number(this.currentNpc.id));
            this.score += points;
            this.statusLabel.textContent = this.isComplete()
                ? `Semua NPC selesai. Skor beta ${this.score}.`
                : `Jawaban benar. +${points} poin beta lokal.`;
            this.updateNpcMarkers();
        } else {
            this.statusLabel.textContent = 'Jawaban belum tepat. NPC bisa dicoba ulang.';
        }

        this.updateHud();
        this.updateDialog();
        this.updateMinimap(true);
    }

    closeNpcDialog() {
        this.currentNpc = null;
        this.answerResult = null;
        this.updateDialog();
        this.statusLabel.textContent = this.isComplete()
            ? 'Semua NPC selesai dijawab di beta lokal.'
            : this.initialStatus;
        this.updateOrientationHint();
    }

    updateDialog() {
        if (!this.dialogHost) {
            return;
        }

        if (!this.currentNpc) {
            this.dialogHost.hidden = true;
            this.dialogHost.innerHTML = '';
            return;
        }

        const npc = this.currentNpc;
        const choices = Array.isArray(npc.choices) ? npc.choices : [];
        const choiceHtml = choices.map((choice, index) => `
            <button type="button" class="rpg-beta-choice" data-beta-answer="${index}" ${this.answerResult ? 'disabled' : ''}>
                <span>${String.fromCharCode(65 + index)}</span>
                ${escapeHtml(choice)}
            </button>
        `).join('');
        const resultHtml = this.answerResult ? `
            <div class="rpg-beta-answer-result ${this.answerResult.correct ? 'is-correct' : 'is-wrong'}">
                <strong>${this.answerResult.correct ? 'Benar' : 'Kurang tepat'}</strong>
                <p>${this.answerResult.correct ? `+${this.answerResult.points} poin beta lokal` : 'Tutup dan kunjungi NPC ini lagi untuk mencoba ulang.'}</p>
                <button type="button" data-beta-close-dialog>${this.answerResult.correct ? 'Lanjutkan' : 'Coba lagi nanti'}</button>
            </div>
        ` : `<div class="rpg-beta-choice-list">${choiceHtml || '<p class="rpg-beta-empty-question">NPC ini belum punya pilihan jawaban.</p>'}</div>`;

        this.dialogHost.innerHTML = `
            <div class="rpg-beta-dialog-backdrop">
                <section class="rpg-beta-dialog-card" aria-label="Pertanyaan NPC beta">
                    <header>
                        <span>${escapeHtml(npc.avatar || 'NPC')}</span>
                        <div>
                            <strong>${escapeHtml(npc.label || 'NPC')}</strong>
                            <small>${Number(npc.points || 0)} poin beta lokal</small>
                        </div>
                    </header>
                    <div class="rpg-beta-dialog-body">
                        <div class="rpg-beta-question">${escapeHtml(npc.question || 'Pertanyaan belum tersedia.')}</div>
                        ${resultHtml}
                    </div>
                </section>
            </div>
        `;
        this.dialogHost.hidden = false;
    }

    isComplete() {
        return this.npcPoints.length > 0 && this.answeredNpcIds.size >= this.npcPoints.length;
    }

    updateNpcMarkers() {
        this.npcPoints.forEach((point) => {
            if (!point.marker) {
                return;
            }

            const answered = this.answeredNpcIds.has(Number(point.id));
            point.marker.traverse((child) => {
                if (child.material) {
                    child.material.transparent = answered;
                    child.material.opacity = answered ? 0.36 : 1;
                }
            });
        });
    }

    generatePickups() {
        const occupied = new Set([
            `${this.startCell.col},${this.startCell.row}`,
            ...this.npcPoints.map((point) => `${point.col},${point.row}`),
            ...this.enemies.map((enemy) => `${enemy.col},${enemy.row}`),
        ]);
        const candidates = this.openCells()
            .filter((cell) => !occupied.has(`${cell.col},${cell.row}`))
            .sort(() => Math.random() - 0.5);
        const pickups = [];

        for (let i = 0; i < this.settings.shieldPickupsCount && candidates.length; i += 1) {
            pickups.push({ id: `shield-${i}`, type: 'shield', ...candidates.pop() });
        }

        for (let i = 0; i < this.settings.ammoPickupsCount && candidates.length; i += 1) {
            pickups.push({ id: `ammo-${i}`, type: 'ammo', ...candidates.pop() });
        }

        return pickups;
    }

    collectPickupAtPlayer() {
        const cell = this.worldToCell(this.player.x, this.player.z);
        const index = this.pickups.findIndex((pickup) => pickup.col === cell.col && pickup.row === cell.row);
        if (index < 0) {
            return;
        }

        const pickup = this.pickups.splice(index, 1)[0];
        if (pickup.type === 'shield') {
            this.shieldUntil = performance.now() + (this.settings.shieldDurationSeconds * 1000);
            this.statusLabel.textContent = `Tameng aktif ${this.settings.shieldDurationSeconds} detik.`;
            this.playSound('pickup-shield');
        } else {
            this.ammo += this.settings.ammoPerPickup;
            this.statusLabel.textContent = `Peluru +${this.settings.ammoPerPickup}. Auto shoot aktif saat musuh sejajar.`;
            this.playSound('pickup-ammo');
        }

        this.syncPickupObjects();
        this.updateHud();
        window.setTimeout(() => this.respawnPickup(pickup.type), PICKUP_RESPAWN_MS);
    }

    respawnPickup(type) {
        const occupied = new Set([
            `${this.worldToCell(this.player.x, this.player.z).col},${this.worldToCell(this.player.x, this.player.z).row}`,
            ...this.pickups.map((pickup) => `${pickup.col},${pickup.row}`),
            ...this.npcPoints.map((point) => `${point.col},${point.row}`),
            ...this.enemies.map((enemy) => `${enemy.col},${enemy.row}`),
        ]);
        const candidates = this.openCells().filter((cell) => !occupied.has(`${cell.col},${cell.row}`));
        if (!candidates.length) {
            return;
        }

        const target = candidates[Math.floor(Math.random() * candidates.length)];
        this.pickups.push({ id: `${type}-${Date.now()}`, type, ...target });
        this.syncPickupObjects();
    }

    updateShieldState() {
        const active = this.shieldUntil > performance.now();
        if (this.shieldVignette) {
            this.shieldVignette.hidden = !active;
        }
        this.updatePlayerEquipment();
        this.updateHud();
    }

    updateEnemies() {
        const now = performance.now();
        const playerCell = this.worldToCell(this.player.x, this.player.z);

        this.enemies.forEach((enemy) => {
            if (enemy.disabledUntil && now < enemy.disabledUntil) {
                return;
            }

            if (enemy.disabledUntil && now >= enemy.disabledUntil) {
                enemy.disabledUntil = 0;
                enemy.col = enemy.spawnCol;
                enemy.row = enemy.spawnRow;
                const spawn = this.cellToWorld(enemy.col, enemy.row);
                enemy.visualX = spawn.x;
                enemy.visualZ = spawn.z;
                const object = this.enemyObjects.get(enemy.id);
                if (object) {
                    object.position.x = spawn.x;
                    object.position.z = spawn.z;
                    object.userData.targetX = spawn.x;
                    object.userData.targetZ = spawn.z;
                }
            }

            if (enemy.nextMoveAt && now < enemy.nextMoveAt) {
                return;
            }

            const next = this.pickEnemyStep(enemy, playerCell);
            enemy.nextMoveAt = now + this.enemyMoveInterval(enemy);
            if (next) {
                enemy.lastCol = enemy.col;
                enemy.lastRow = enemy.row;
                enemy.col = next.col;
                enemy.row = next.row;
            }
        });

        this.syncEnemyObjects();
        this.updateMinimap(false);
    }

    pickEnemyStep(enemy, playerCell) {
        const intelligence = enemy.intelligenceLevel || 'normal';
        const alertRadius = Number(enemy.alertRadius || 6) + this.challengePressure();
        const playerDistance = distance(enemy, playerCell);
        enemy.alerted = playerDistance <= alertRadius;

        if (!enemy.alerted) {
            return this.pickEnemyPatrolStep(enemy);
        }

        const randomChance = { low: 0.56, normal: 0.24, high: 0.08 }[intelligence] || 0.24;
        let directions = [];

        if (Math.random() < randomChance) {
            directions = shuffle([[0, 1], [0, -1], [1, 0], [-1, 0]]);
        } else {
            const dc = playerCell.col - enemy.col;
            const dr = playerCell.row - enemy.row;
            const horizontal = [dc > 0 ? 1 : -1, 0];
            const vertical = [0, dr > 0 ? 1 : -1];
            directions = Math.abs(dc) >= Math.abs(dr)
                ? [horizontal, vertical]
                : [vertical, horizontal];
            directions = directions.concat(intelligence === 'high'
                ? [[1, 0], [-1, 0], [0, 1], [0, -1]]
                : shuffle([[1, 0], [-1, 0], [0, 1], [0, -1]]));
        }

        for (const [dc, dr] of directions) {
            const next = { col: enemy.col + dc, row: enemy.row + dr };
            if (this.canEnemyMoveTo(next.col, next.row, enemy)) {
                return next;
            }
        }

        return null;
    }

    pickEnemyPatrolStep(enemy) {
        const primary = enemy.patrolAxis === 'vertical'
            ? [[0, enemy.patrolDirection || 1], [1, 0], [-1, 0], [0, -(enemy.patrolDirection || 1)]]
            : [[enemy.patrolDirection || 1, 0], [0, 1], [0, -1], [-(enemy.patrolDirection || 1), 0]];

        for (const [dc, dr] of primary) {
            const next = { col: enemy.col + dc, row: enemy.row + dr };
            if (this.canEnemyMoveTo(next.col, next.row, enemy)) {
                if ((enemy.patrolAxis === 'vertical' && dr === -(enemy.patrolDirection || 1))
                    || (enemy.patrolAxis !== 'vertical' && dc === -(enemy.patrolDirection || 1))) {
                    enemy.patrolDirection *= -1;
                }
                return next;
            }
        }

        enemy.patrolDirection *= -1;
        return null;
    }

    canEnemyMoveTo(col, row, currentEnemy) {
        if (!this.maze[row] || this.maze[row][col] !== 0) {
            return false;
        }

        if (this.npcPoints.some((point) => point.col === col && point.row === row)) {
            return false;
        }

        return !this.enemies.some((enemy) => enemy !== currentEnemy && !enemy.disabledUntil && enemy.col === col && enemy.row === row);
    }

    enemyMoveInterval(enemy) {
        const base = { easy: 2300, medium: 1750, hard: 1350 }[this.settings.difficulty] || 1750;
        const factor = { slow: 1.45, normal: 1.12, fast: 0.92 }[enemy.speedLevel || 'normal'] || 1.12;
        const pressure = Math.max(0.72, 1 - (this.challengePressure() * 0.055));
        const patrolEase = enemy.alerted ? 1 : 1.28;
        return Math.round(base * factor * pressure * patrolEase);
    }

    challengePressure() {
        const answeredRatio = this.npcPoints.length
            ? this.answeredNpcIds.size / this.npcPoints.length
            : 0;
        const difficultyPressure = { easy: 0, medium: 1, hard: 2 }[this.settings.difficulty] || 0;
        return Math.min(4, difficultyPressure + Math.floor(answeredRatio * 3));
    }

    checkEnemyCatch() {
        const playerCell = this.worldToCell(this.player.x, this.player.z);
        const enemy = this.enemies.find((item) => !item.disabledUntil && item.col === playerCell.col && item.row === playerCell.row);
        if (!enemy) {
            return;
        }

        const enemyWorld = this.cellToWorld(enemy.col, enemy.row);
        if (Number.isFinite(enemy.visualX) && Number.isFinite(enemy.visualZ)) {
            const visualDistance = Math.hypot(this.player.x - enemy.visualX, this.player.z - enemy.visualZ);
            const targetDistance = Math.hypot(enemy.visualX - enemyWorld.x, enemy.visualZ - enemyWorld.z);
            if (visualDistance > TILE_SIZE * 0.34 || targetDistance > TILE_SIZE * 0.12) {
                return;
            }
        }

        if (this.shieldUntil > performance.now()) {
            this.shieldUntil = 0;
            enemy.disabledUntil = performance.now() + 1400;
            this.statusLabel.textContent = 'Tameng menyerap serangan musuh.';
            this.playSound('shield-hit');
            this.syncEnemyObjects();
            this.updateHud();
            return;
        }

        const pos = this.cellToWorld(this.startCell.col, this.startCell.row);
        this.player.set(pos.x, PLAYER_HEIGHT, pos.z);
        this.velocity.set(0, 0, 0);
        this.score = Math.max(0, this.score - 5);
        this.statusLabel.textContent = 'Tertangkap musuh. Kembali ke awal, skor beta -5.';
        this.playSound('caught');
        this.updateHud();
        this.updateMinimap(true);
        this.syncCurrentCell(true);
    }

    tryAutoShoot() {
        if (this.ammo <= 0 || performance.now() - this.lastAutoShotAt < 450) {
            return false;
        }

        return this.shootForward(false);
    }

    shootForward(manual = false) {
        if (this.ammo <= 0) {
            if (manual) {
                this.statusLabel.textContent = 'Peluru beta habis. Cari pickup kuning.';
                this.playSound('miss');
            }
            return false;
        }

        const direction = this.cardinalDirection();
        const target = this.findShootTarget(direction, manual ? 5 : this.autoShootRange());
        if (!target) {
            if (manual) {
                this.ammo -= 1;
                this.statusLabel.textContent = 'Tembakan beta meleset.';
                this.playSound('shot');
                this.playSound('miss');
                this.kickWeapon();
                this.flashShot(direction, 4);
                this.updateHud();
            }
            return false;
        }

        this.ammo -= 1;
        this.lastAutoShotAt = performance.now();
        this.playSound('shot');
        this.playSound('hit');
        this.kickWeapon();
        this.flashShot(direction, Math.max(1, distance(this.worldToCell(this.player.x, this.player.z), target)));
        const enemy = this.enemies[target.index];
        enemy.disabledUntil = performance.now() + 1200;
        enemy.col = enemy.spawnCol;
        enemy.row = enemy.spawnRow;
        const spawn = this.cellToWorld(enemy.spawnCol, enemy.spawnRow);
        enemy.visualX = spawn.x;
        enemy.visualZ = spawn.z;
        const object = this.enemyObjects.get(enemy.id);
        if (object) {
            object.position.x = spawn.x;
            object.position.z = spawn.z;
            object.userData.targetX = spawn.x;
            object.userData.targetZ = spawn.z;
        }
        this.statusLabel.textContent = manual ? 'Musuh terkena tembakan.' : 'Auto shoot menembak musuh sejajar.';
        this.syncEnemyObjects();
        this.updateHud();
        return true;
    }

    autoShootRange() {
        return { easy: 4, medium: 3, hard: 2 }[this.settings.difficulty] || AUTO_SHOOT_RANGE;
    }

    findShootTarget(direction, range) {
        const cell = this.worldToCell(this.player.x, this.player.z);

        for (let step = 1; step <= range; step += 1) {
            const col = cell.col + direction.col * step;
            const row = cell.row + direction.row * step;
            if (!this.maze[row] || this.maze[row][col] === 1) {
                return null;
            }

            const index = this.enemies.findIndex((enemy) => !enemy.disabledUntil && enemy.col === col && enemy.row === row);
            if (index >= 0) {
                return { index, col, row };
            }
        }

        return null;
    }

    cardinalDirection() {
        const normalized = ((this.yaw % (Math.PI * 2)) + Math.PI * 2) % (Math.PI * 2);
        const index = Math.round(normalized / (Math.PI / 2)) % 4;
        return [
            { col: 0, row: 1 },
            { col: 1, row: 0 },
            { col: 0, row: -1 },
            { col: -1, row: 0 },
        ][index];
    }

    openCells() {
        const cells = [];
        this.maze.forEach((row, rowIndex) => {
            row.forEach((cell, colIndex) => {
                if (cell === 0) {
                    cells.push({ col: colIndex, row: rowIndex });
                }
            });
        });
        return cells;
    }

    updateCamera() {
        if (!this.player) {
            return;
        }

        const bob = Math.sin(this.steps * 3.4) * Math.min(0.045, this.velocity.length() * 0.008);
        this.camera.position.set(this.player.x, PLAYER_HEIGHT + bob, this.player.z);
        const look = new THREE.Vector3(
            this.player.x + Math.sin(this.yaw) * TILE_SIZE,
            PLAYER_HEIGHT - 0.08,
            this.player.z + Math.cos(this.yaw) * TILE_SIZE,
        );
        this.camera.lookAt(look);
    }

    updateGoal(delta) {
        if (this.goal) {
            this.goal.rotation.y += delta * 0.9;
        }

        if (this.goalReached || !this.player) {
            return;
        }

        const goalPos = this.cellToWorld(this.goalCell.col, this.goalCell.row);
        if (Math.hypot(this.player.x - goalPos.x, this.player.z - goalPos.z) < TILE_SIZE * 0.55) {
            this.goalReached = true;
            this.statusLabel.textContent = this.goalStatus;
        }
    }

    updateHud() {
        this.updatePlayerEquipment();
        this.stepsLabel.textContent = String(Math.floor(this.steps));
        this.scoreLabel.textContent = String(this.score);
        this.npcsLabel.textContent = `${this.answeredNpcIds.size}/${this.npcPoints.length}`;
        this.ammoLabel.textContent = String(this.ammo);
        const secondsLeft = Math.max(0, Math.ceil((this.shieldUntil - performance.now()) / 1000));
        this.shieldLabel.textContent = secondsLeft > 0 ? `${secondsLeft}d` : 'OFF';
        if (this.onlineLabel) {
            this.onlineLabel.textContent = String(Math.max(1, Number(this.activePlayersCount || 1)));
        }
    }

    updatePlayerEquipment() {
        if (!this.playerViewModel) {
            return;
        }

        const weapon = this.playerViewModel.userData.weapon;
        const shield = this.playerViewModel.userData.shield;
        if (weapon) {
            const stillRecoiling = Number(weapon.userData.kickStartedAt || 0) > 0
                && performance.now() - Number(weapon.userData.kickStartedAt || 0) < 220;
            weapon.visible = this.ammo > 0 || stillRecoiling;
        }
        if (shield) {
            shield.visible = this.shieldUntil > performance.now();
        }
    }

    updateHeading() {
        const normalized = ((this.yaw % (Math.PI * 2)) + Math.PI * 2) % (Math.PI * 2);
        const labels = ['Selatan', 'Timur', 'Utara', 'Barat'];
        const index = Math.round(normalized / (Math.PI / 2)) % 4;
        this.headingLabel.textContent = labels[index];
    }

    updateMinimap(force) {
        if (!this.minimap || (!force && !this.player)) {
            return;
        }

        const playerCell = this.player ? this.worldToCell(this.player.x, this.player.z) : this.startCell;
        this.minimap.style.gridTemplateColumns = `repeat(${this.maze.length}, minmax(0, 1fr))`;
        const cells = [];

        const npcCells = new Set(this.npcPoints.map((point) => `${point.col},${point.row}`));
        const answeredNpcCells = new Set(this.npcPoints
            .filter((point) => this.answeredNpcIds.has(Number(point.id)))
            .map((point) => `${point.col},${point.row}`));
        const enemyCells = new Set(this.enemies
            .filter((enemy) => !enemy.disabledUntil)
            .map((enemy) => `${enemy.col},${enemy.row}`));
        const onlineCells = new Set(this.onlinePlayers
            .map((player) => this.mapToMazeCell(player.pos_x, player.pos_y))
            .filter(Boolean)
            .map((cell) => `${cell.col},${cell.row}`));
        const pickupCells = new Set(this.pickups.map((pickup) => `${pickup.col},${pickup.row}`));
        this.maze.forEach((row, rowIndex) => {
            row.forEach((cell, colIndex) => {
                const key = `${colIndex},${rowIndex}`;
                let className = '';
                if (cell === 1) {
                    className = 'is-wall';
                } else if (playerCell.col === colIndex && playerCell.row === rowIndex) {
                    className = 'is-player';
                } else if (enemyCells.has(key)) {
                    className = 'is-enemy';
                } else if (onlineCells.has(key)) {
                    className = 'is-online';
                } else if (answeredNpcCells.has(key)) {
                    className = 'is-npc-done';
                } else if (npcCells.has(key)) {
                    className = 'is-npc';
                } else if (pickupCells.has(key)) {
                    className = 'is-pickup';
                }
                cells.push(`<span class="${className}"></span>`);
            });
        });

        this.minimap.innerHTML = cells.join('');
    }

    enterFullscreen() {
        this.tryMobileLandscape(true);
    }

    tryMobileLandscape(force = false) {
        if (!this.isMobileViewport()) {
            if (force && !document.fullscreenElement && this.root.requestFullscreen) {
                this.root.requestFullscreen({ navigationUI: 'hide' }).catch(() => null);
            }
            this.updateOrientationHint();
            return;
        }

        if (this.orientation.locking) {
            this.updateOrientationHint();
            return;
        }

        if (this.orientation.attempted && !force) {
            this.updateOrientationHint();
            return;
        }

        this.orientation.attempted = true;
        this.orientation.locking = true;
        const requestFullscreen = !document.fullscreenElement && this.root.requestFullscreen
            ? this.root.requestFullscreen({ navigationUI: 'hide' }).catch(() => null)
            : Promise.resolve();

        requestFullscreen
            .then(() => {
                if (screen.orientation && typeof screen.orientation.lock === 'function') {
                    return screen.orientation.lock('landscape').catch(() => null);
                }

                return null;
            })
            .catch(() => null)
            .finally(() => {
                this.orientation.locking = false;
                window.setTimeout(() => this.updateOrientationHint(), 180);
            });
    }

    updateOrientationHint() {
        if (!this.orientationHint) {
            return;
        }

        const shouldShow = this.isMobileViewport()
            && this.isPortraitViewport()
            && !this.currentNpc;
        this.orientationHint.hidden = !shouldShow;
        this.root.classList.toggle('is-mobile-portrait', shouldShow);
    }

    isMobileViewport() {
        return window.matchMedia('(pointer: coarse)').matches || window.matchMedia('(max-width: 900px)').matches;
    }

    isPortraitViewport() {
        return window.matchMedia('(orientation: portrait)').matches
            || window.innerHeight > window.innerWidth;
    }

    cellToWorld(col, row) {
        const center = (this.maze.length - 1) / 2;
        return {
            x: (col - center) * TILE_SIZE,
            z: (row - center) * TILE_SIZE,
        };
    }

    mapToMazeCell(x, y) {
        const mapX = clampInteger(x, 0, this.gridSize - 1);
        const mapY = clampInteger(y, 0, this.gridSize - 1);
        return {
            col: mapX + 1,
            row: this.gridSize - mapY,
        };
    }

    worldToMapCell(x, z) {
        const cell = this.worldToCell(x, z);
        const mapX = cell.col - 1;
        const mapY = this.gridSize - cell.row;

        if (mapX < 0 || mapX >= this.gridSize || mapY < 0 || mapY >= this.gridSize) {
            return null;
        }

        return {
            x: mapX,
            y: mapY,
        };
    }

    worldToCell(x, z) {
        const center = (this.maze.length - 1) / 2;
        return {
            col: Math.round(x / TILE_SIZE + center),
            row: Math.round(z / TILE_SIZE + center),
        };
    }

    isBlocked(x, z) {
        const samples = [
            [x - PLAYER_RADIUS, z - PLAYER_RADIUS],
            [x + PLAYER_RADIUS, z - PLAYER_RADIUS],
            [x - PLAYER_RADIUS, z + PLAYER_RADIUS],
            [x + PLAYER_RADIUS, z + PLAYER_RADIUS],
        ];

        return samples.some(([sx, sz]) => {
            const cell = this.worldToCell(sx, sz);
            return !this.maze[cell.row] || this.maze[cell.row][cell.col] !== 0;
        });
    }

    resize(force = false) {
        const rect = this.root.getBoundingClientRect();
        const width = Math.max(1, Math.floor(rect.width));
        const height = Math.max(1, Math.floor(rect.height));
        const pixelRatio = this.renderer.getPixelRatio();

        if (
            !force
            &&
            this.renderer.domElement.width === Math.floor(width * pixelRatio)
            && this.renderer.domElement.height === Math.floor(height * pixelRatio)
        ) {
            return;
        }

        this.camera.aspect = width / height;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(width, height, false);
    }

    disposeObject(object) {
        object.traverse((child) => {
            if (child.geometry) {
                child.geometry.dispose();
            }
            if (child.material) {
                const materials = Array.isArray(child.material) ? child.material : [child.material];
                materials.forEach((material) => {
                    if (material.map) {
                        material.map.dispose();
                    }
                    material.dispose();
                });
            }
        });
    }

    clearGroup(group) {
        while (group.children.length) {
            const child = group.children.pop();
            this.disposeObject(child);
        }
    }
}

function makeBrickTexture(baseColor = 0x6f6f68, capColor = 0x60605a, lineColor = 0x1f2933) {
    const canvas = document.createElement('canvas');
    canvas.width = 256;
    canvas.height = 256;
    const context = canvas.getContext('2d');
    const base = hexToRgb(baseColor);
    const cap = hexToRgb(capColor);
    const line = hexToRgb(lineColor);
    context.fillStyle = `rgb(${base.r}, ${base.g}, ${base.b})`;
    context.fillRect(0, 0, 256, 256);

    for (let y = 0; y < 256; y += 64) {
        const offset = (y / 64) % 2 === 0 ? 0 : 48;
        for (let x = -offset; x < 256; x += 96) {
            const shade = ((x + y) % 32) - 12;
            context.fillStyle = `rgb(${clampColor(cap.r + shade)}, ${clampColor(cap.g + shade)}, ${clampColor(cap.b + shade)})`;
            context.fillRect(x + 2, y + 2, 92, 60);
        }
    }

    context.strokeStyle = `rgba(${line.r}, ${line.g}, ${line.b}, 0.55)`;
    context.lineWidth = 3;
    for (let y = 0; y <= 256; y += 64) {
        context.beginPath();
        context.moveTo(0, y);
        context.lineTo(256, y);
        context.stroke();
    }

    return new THREE.CanvasTexture(canvas);
}

function makeGridTexture(base, alt, line) {
    const canvas = document.createElement('canvas');
    canvas.width = 256;
    canvas.height = 256;
    const context = canvas.getContext('2d');
    context.fillStyle = base;
    context.fillRect(0, 0, 256, 256);
    context.fillStyle = alt;
    context.fillRect(0, 0, 128, 128);
    context.fillRect(128, 128, 128, 128);
    context.strokeStyle = line;
    context.lineWidth = 4;
    context.strokeRect(0, 0, 256, 256);
    context.beginPath();
    context.moveTo(128, 0);
    context.lineTo(128, 256);
    context.moveTo(0, 128);
    context.lineTo(256, 128);
    context.stroke();
    return new THREE.CanvasTexture(canvas);
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

function clampInteger(value, min, max) {
    const number = Number(value);
    if (!Number.isFinite(number)) {
        return min;
    }

    return Math.min(Math.max(Math.round(number), min), max);
}

function clampNumber(value, min, max) {
    const number = Number(value);
    if (!Number.isFinite(number)) {
        return min;
    }

    return Math.min(Math.max(number, min), max);
}

function parseHexColor(value, fallback) {
    const text = String(value || '').trim();
    const match = text.match(/^#?([0-9a-f]{6})$/i);
    if (!match) {
        return fallback;
    }

    return Number.parseInt(match[1], 16);
}

function mixHexColor(color, target, amount = 0.5) {
    const ratio = clampNumber(amount, 0, 1);
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

function darkenHexColor(color, factor = 0.75) {
    const source = Number(color || 0);
    const r = clampColor(((source >> 16) & 255) * factor);
    const g = clampColor(((source >> 8) & 255) * factor);
    const b = clampColor((source & 255) * factor);
    return (r << 16) + (g << 8) + b;
}

function hexToRgb(color) {
    const source = Number(color || 0);
    return {
        r: (source >> 16) & 255,
        g: (source >> 8) & 255,
        b: source & 255,
    };
}

function clampColor(value) {
    return Math.max(0, Math.min(255, Math.round(Number(value) || 0)));
}

function cellNoise(col, row) {
    const x = Math.sin((Number(col) * 127.1) + (Number(row) * 311.7)) * 43758.5453;
    return x - Math.floor(x);
}

function distance(a, b) {
    return Math.abs(Number(a.col || 0) - Number(b.col || 0))
        + Math.abs(Number(a.row || 0) - Number(b.row || 0));
}

function shuffle(items) {
    return items
        .slice()
        .sort(() => Math.random() - 0.5);
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function bootBetaMazes() {
    document.querySelectorAll('[data-rpg-beta-3d]').forEach((root) => {
        if (root.__pkgRpgBeta3d) {
            return;
        }

        root.__pkgRpgBeta3d = new RpgBetaMaze(root);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootBetaMazes);
} else {
    bootBetaMazes();
}
