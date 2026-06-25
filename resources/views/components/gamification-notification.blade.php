{{-- Gamification Notification Component --}}
<div id="gamificationNotification" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-black/60">
    <div id="notificationContent" class="bg-white rounded-2xl max-w-sm w-full p-8 text-center transform scale-95 opacity-0 transition-all duration-300">
        {{-- Level Up Notification --}}
        <div id="levelUpNotif" class="hidden">
            <div class="text-6xl mb-4 animate-bounce" id="levelIcon">LVL</div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Level Up!</h2>
            <p class="text-gray-600 mb-4">Selamat! Kamu naik ke</p>
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-white font-bold text-lg mb-4" id="levelBadge" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <span id="levelName">Level 2</span>
            </div>
            <p class="text-sm text-gray-500" id="levelBenefits"></p>
        </div>
        
        {{-- Badge Earned Notification --}}
        <div id="badgeNotif" class="hidden">
            <div class="relative inline-block mb-4">
                {{-- Animated rings --}}
                <div class="badge-ring-1"></div>
                <div class="badge-ring-2"></div>
                <div class="badge-ring-3"></div>
                {{-- Badge icon with shine effect --}}
                <div class="badge-icon-container">
                    <div class="text-7xl badge-icon-bounce" id="badgeIcon">PIN</div>
                    <div class="badge-shine"></div>
                </div>
                {{-- Sparkles --}}
                <div class="badge-sparkle sparkle-1">*</div>
                <div class="badge-sparkle sparkle-2">+</div>
                <div class="badge-sparkle sparkle-3">*</div>
                <div class="badge-sparkle sparkle-4">+</div>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2 badge-title-animate">Pin Baru!</h2>
            <p class="text-gray-600 mb-2">Kamu mendapatkan pin</p>
            <div class="inline-block px-4 py-2 rounded-lg font-bold text-lg mb-2 badge-name-animate" id="badgeName" style="background-color: #FEF3C7; color: #92400E;">
                Nama Pin
            </div>
            <p class="text-sm text-gray-500 mb-4" id="badgeDesc"></p>
            <div class="inline-flex items-center gap-1 text-yellow-600 font-medium badge-points-animate">
                <span>+</span><span id="badgePoints">50</span><span>poin</span>
            </div>
        </div>
        
        {{-- Points Earned Notification --}}
        <div id="pointsNotif" class="hidden">
            <div class="text-6xl mb-4">PTS</div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Poin Diterima!</h2>
            <div class="text-4xl font-bold text-green-600 mb-2" id="pointsAmount">+10</div>
            <p class="text-gray-600" id="pointsReason">Kehadiran tepat waktu</p>
        </div>
        
        <button onclick="closeGamificationNotification()" class="mt-6 px-6 py-2 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-full font-medium hover:shadow-lg transition">
            Lanjutkan
        </button>
    </div>
</div>

<style>
@keyframes confetti {
    0% { transform: translateY(0) rotate(0deg); opacity: 1; }
    100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
}

.confetti {
    position: fixed;
    width: 10px;
    height: 10px;
    top: -10px;
    animation: confetti 3s ease-out forwards;
}

@keyframes shine {
    0% { background-position: -200% center; }
    100% { background-position: 200% center; }
}

.shine-effect {
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    background-size: 200% 100%;
    animation: shine 2s infinite;
}

/* Badge Animation Styles */
@keyframes badgeBounce {
    0%, 100% { transform: scale(1); }
    25% { transform: scale(1.2); }
    50% { transform: scale(0.95); }
    75% { transform: scale(1.1); }
}

@keyframes badgeRing {
    0% { transform: scale(0.5); opacity: 1; }
    100% { transform: scale(2); opacity: 0; }
}

@keyframes sparkle {
    0%, 100% { transform: scale(0) rotate(0deg); opacity: 0; }
    50% { transform: scale(1) rotate(180deg); opacity: 1; }
}

@keyframes badgeShine {
    0% { transform: translateX(-100%) rotate(45deg); }
    100% { transform: translateX(200%) rotate(45deg); }
}

@keyframes titlePop {
    0% { transform: scale(0); opacity: 0; }
    60% { transform: scale(1.2); }
    100% { transform: scale(1); opacity: 1; }
}

@keyframes slideUp {
    0% { transform: translateY(20px); opacity: 0; }
    100% { transform: translateY(0); opacity: 1; }
}

@keyframes pointsGlow {
    0%, 100% { text-shadow: 0 0 5px rgba(234, 179, 8, 0.5); }
    50% { text-shadow: 0 0 20px rgba(234, 179, 8, 0.8), 0 0 30px rgba(234, 179, 8, 0.6); }
}

.badge-icon-container {
    position: relative;
    display: inline-block;
    overflow: hidden;
}

.badge-icon-bounce {
    animation: badgeBounce 0.8s ease-out;
}

.badge-shine {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.6), transparent);
    animation: badgeShine 1.5s ease-in-out infinite;
    pointer-events: none;
}

.badge-ring-1, .badge-ring-2, .badge-ring-3 {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 80px;
    height: 80px;
    margin: -40px 0 0 -40px;
    border: 3px solid #F59E0B;
    border-radius: 50%;
    animation: badgeRing 1.5s ease-out infinite;
}

.badge-ring-2 { animation-delay: 0.3s; border-color: #FBBF24; }
.badge-ring-3 { animation-delay: 0.6s; border-color: #FCD34D; }

.badge-sparkle {
    position: absolute;
    font-size: 1.5rem;
    animation: sparkle 1.5s ease-in-out infinite;
}

.sparkle-1 { top: -10px; left: 0; animation-delay: 0s; }
.sparkle-2 { top: 0; right: -10px; animation-delay: 0.3s; }
.sparkle-3 { bottom: -10px; right: 0; animation-delay: 0.6s; }
.sparkle-4 { bottom: 0; left: -10px; animation-delay: 0.9s; }

.badge-title-animate {
    animation: titlePop 0.5s ease-out 0.2s both;
}

.badge-name-animate {
    animation: slideUp 0.5s ease-out 0.4s both;
}

.badge-points-animate {
    animation: slideUp 0.5s ease-out 0.6s both, pointsGlow 1.5s ease-in-out 1s infinite;
}

@keyframes starBurst {
    0% {
        transform: translate(0, 0) scale(0) rotate(0deg);
        opacity: 1;
    }
    100% {
        transform: translate(
            calc(cos(var(--angle)) * var(--distance)),
            calc(sin(var(--angle)) * var(--distance))
        ) scale(1) rotate(360deg);
        opacity: 0;
    }
}

.badge-star {
    will-change: transform, opacity;
}
</style>

<script>
let notificationQueue = [];
let isShowingNotification = false;

function showGamificationNotification(type, data) {
    notificationQueue.push({ type, data });
    processNotificationQueue();
}

function processNotificationQueue() {
    if (isShowingNotification || notificationQueue.length === 0) return;
    
    isShowingNotification = true;
    const { type, data } = notificationQueue.shift();
    
    const container = document.getElementById('gamificationNotification');
    const content = document.getElementById('notificationContent');
    
    // Hide all notification types
    document.getElementById('levelUpNotif').classList.add('hidden');
    document.getElementById('badgeNotif').classList.add('hidden');
    document.getElementById('pointsNotif').classList.add('hidden');
    
    if (type === 'level_up') {
        document.getElementById('levelUpNotif').classList.remove('hidden');
        document.getElementById('levelIcon').textContent = data.icon || 'LVL';
        document.getElementById('levelName').textContent = data.name || 'Level Baru';
        document.getElementById('levelBadge').style.background = `linear-gradient(135deg, ${data.color || '#667eea'} 0%, ${data.color || '#764ba2'} 100%)`;
        document.getElementById('levelBenefits').textContent = data.benefits || '';
        createConfetti();
    } else if (type === 'badge') {
        document.getElementById('badgeNotif').classList.remove('hidden');
        document.getElementById('badgeIcon').textContent = data.icon || 'PIN';
        document.getElementById('badgeName').textContent = data.name || 'Pin Baru';
        document.getElementById('badgeName').style.backgroundColor = (data.color || '#3B82F6') + '20';
        document.getElementById('badgeName').style.color = data.color || '#3B82F6';
        document.getElementById('badgeDesc').textContent = data.description || '';
        document.getElementById('badgePoints').textContent = data.poin_reward || data.points || 0;
        createBadgeStars(data.color || '#F59E0B');
    } else if (type === 'points') {
        document.getElementById('pointsNotif').classList.remove('hidden');
        document.getElementById('pointsAmount').textContent = '+' + (data.amount || 0);
        document.getElementById('pointsReason').textContent = data.reason || '';
    }
    container.classList.add('flex');
    
    // Animate in
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 50);
}

function closeGamificationNotification() {
    const container = document.getElementById('gamificationNotification');
    const content = document.getElementById('notificationContent');
    
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        container.classList.add('hidden');
        container.classList.remove('flex');
        isShowingNotification = false;
        processNotificationQueue();
    }, 300);
}

function createConfetti() {
    const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA0DD', '#98D8C8'];
    
    for (let i = 0; i < 50; i++) {
        setTimeout(() => {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            confetti.style.left = Math.random() * 100 + 'vw';
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.borderRadius = Math.random() > 0.5 ? '50%' : '0';
            confetti.style.animationDuration = (Math.random() * 2 + 2) + 's';
            document.body.appendChild(confetti);
            
            setTimeout(() => confetti.remove(), 4000);
        }, i * 30);
    }
}

function createBadgeStars(color) {
    const stars = ['*', '+', 'STAR', 'PTS'];
    const container = document.getElementById('notificationContent');
    const rect = container.getBoundingClientRect();
    
    for (let i = 0; i < 20; i++) {
        setTimeout(() => {
            const star = document.createElement('div');
            star.className = 'badge-star';
            star.textContent = stars[Math.floor(Math.random() * stars.length)];
            star.style.cssText = `
                position: fixed;
                font-size: ${Math.random() * 20 + 15}px;
                left: ${rect.left + rect.width / 2}px;
                top: ${rect.top + rect.height / 2}px;
                pointer-events: none;
                z-index: 1000;
                animation: starBurst 1s ease-out forwards;
                --angle: ${Math.random() * 360}deg;
                --distance: ${Math.random() * 150 + 100}px;
            `;
            document.body.appendChild(star);
            
            setTimeout(() => star.remove(), 1000);
        }, i * 50);
    }
}

// Check for pending notifications on page load
document.addEventListener('DOMContentLoaded', function() {
    const pendingNotifications = window.pendingGamificationNotifications || [];
    pendingNotifications.forEach(n => showGamificationNotification(n.type, n.data));
});
</script>
