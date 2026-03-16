<template>
  <div class="luxury-app">
    <!-- Animated Background -->
    <div class="bg-animation">
      <div class="bg-gradient"></div>
      <div class="particles-container">
        <span v-for="n in 20" :key="n" :style="particleStyle(n)"></span>
      </div>
    </div>

    <Navbar />

    <main class="main-content">
      <div class="container">
        <!-- Hero Section -->
        <section class="hero-section">
          <div class="hero-content">
            <h1 class="hero-title">
              <span class="title-line">Exquisite</span>
              <span class="title-line gold">Dining</span>
              <span class="title-line">Management</span>
            </h1>
            <p class="hero-subtitle">Curating unforgettable culinary experiences</p>
            <div class="hero-line"></div>
          </div>
        </section>

        <!-- Stats Dashboard -->
        <section class="stats-section">
          <div class="stats-grid">
            <div
              v-for="(stat, index) in stats"
              :key="stat.label"
              class="stat-card"
              :style="{ animationDelay: `${index * 0.1}s` }"
              @mouseenter="stat.hover = true"
              @mouseleave="stat.hover = false"
            >
              <div class="stat-glow" :class="{ active: stat.hover }"></div>
              <div class="stat-icon" :class="stat.color">
                <i :class="stat.icon"></i>
              </div>
              <div class="stat-content">
                <div class="stat-number" :class="{ 'count-up': stat.hover }">
                  {{ stat.value }}
                </div>
                <div class="stat-label">{{ stat.label }}</div>
              </div>
              <div class="stat-sparkle" v-if="stat.hover">
                <i v-for="n in 3" :key="n" class="bi bi-star-fill"></i>
              </div>
            </div>
          </div>
        </section>

        <!-- Main Content Grid -->
        <section class="content-section">
          <div class="row g-4">
            <div class="col-lg-4">
              <ReservationForm />
            </div>
            <div class="col-lg-8">
              <ReservationList />
            </div>
          </div>
        </section>
      </div>
    </main>

    <!-- Footer -->
    <footer class="luxury-footer">
      <div class="container">
        <div class="footer-content">
          <div class="footer-brand">
            <i class="bi bi-gem"></i>
            <span>LUXE RESERVE</span>
          </div>
          <div class="footer-line"></div>
          <p class="footer-text">Elevating hospitality through elegant technology</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { computed, reactive, onMounted } from 'vue'
import { useReservationStore } from './stores/reservations'
import Navbar from './components/Navbar.vue'
import ReservationForm from './components/ReservationForm.vue'
import ReservationList from './components/ReservationList.vue'

const store = useReservationStore()

// Fetch reservations on app mount
onMounted(() => {
  store.fetchReservations()
})

const stats = reactive([
  {
    label: 'Total Reservations',
    value: computed(() => store.reservations.length),
    icon: 'bi bi-calendar-check',
    color: 'gold',
    hover: false,
  },
  {
    label: 'Confirmed',
    value: computed(() => store.reservations.filter((r) => r.status === 'confirmed').length),
    icon: 'bi bi-check-circle',
    color: 'success',
    hover: false,
  },
  {
    label: 'Pending',
    value: computed(() => store.reservations.filter((r) => r.status === 'pending').length),
    icon: 'bi bi-hourglass-split',
    color: 'warning',
    hover: false,
  },
  {
    label: 'Total Guests',
    value: computed(() => store.reservations.reduce((sum, r) => sum + r.guests, 0)),
    icon: 'bi bi-people',
    color: 'info',
    hover: false,
  },
])

const particleStyle = (n) => ({
  left: `${Math.random() * 100}%`,
  animationDelay: `${Math.random() * 20}s`,
  animationDuration: `${15 + Math.random() * 10}s`,
  opacity: Math.random() * 0.5 + 0.1,
})
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap');

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.luxury-app {
  min-height: 100vh;
  background: #0a0a0a;
  color: #f4e5c2;
  font-family: 'Inter', sans-serif;
  overflow-x: hidden;
  position: relative;
}

/* Animated Background */
.bg-animation {
  position: fixed;
  inset: 0;
  z-index: 0;
  overflow: hidden;
}

.bg-gradient {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse at 20% 20%, rgba(212, 175, 55, 0.15) 0%, transparent 50%),
    radial-gradient(ellipse at 80% 80%, rgba(212, 175, 55, 0.1) 0%, transparent 50%),
    radial-gradient(ellipse at 50% 50%, rgba(139, 69, 19, 0.1) 0%, transparent 70%);
  animation: bgShift 20s ease-in-out infinite;
}

@keyframes bgShift {
  0%,
  100% {
    transform: scale(1) rotate(0deg);
  }
  50% {
    transform: scale(1.1) rotate(5deg);
  }
}

.particles-container span {
  position: absolute;
  width: 4px;
  height: 4px;
  background: #d4af37;
  border-radius: 50%;
  animation: float linear infinite;
  box-shadow: 0 0 10px #d4af37;
}

@keyframes float {
  0% {
    transform: translateY(100vh) scale(0);
    opacity: 0;
  }
  10% {
    opacity: 1;
  }
  90% {
    opacity: 1;
  }
  100% {
    transform: translateY(-100vh) scale(1.5);
    opacity: 0;
  }
}

/* Main Content */
.main-content {
  position: relative;
  z-index: 1;
  padding-top: 100px;
  padding-bottom: 4rem;
}

/* Hero Section */
.hero-section {
  text-align: center;
  margin-bottom: 4rem;
  animation: fadeInUp 1s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(40px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.hero-title {
  font-family: 'Playfair Display', serif;
  font-size: clamp(2.5rem, 6vw, 4.5rem);
  font-weight: 700;
  line-height: 1.1;
  margin-bottom: 1rem;
}

.title-line {
  display: block;
  opacity: 0;
  animation: slideUp 0.8s ease-out forwards;
}

.title-line:nth-child(1) {
  animation-delay: 0.2s;
}
.title-line:nth-child(2) {
  animation-delay: 0.4s;
}
.title-line:nth-child(3) {
  animation-delay: 0.6s;
}

.title-line.gold {
  background: linear-gradient(135deg, #d4af37 0%, #f4e5c2 50%, #d4af37 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  background-size: 200% 200%;
  animation:
    slideUp 0.8s ease-out 0.4s forwards,
    shimmer 3s ease-in-out infinite;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes shimmer {
  0%,
  100% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
}

.hero-subtitle {
  font-size: 1.2rem;
  color: rgba(244, 229, 194, 0.6);
  letter-spacing: 3px;
  text-transform: uppercase;
  margin-bottom: 2rem;
  opacity: 0;
  animation: fadeIn 1s ease-out 0.8s forwards;
}

@keyframes fadeIn {
  to {
    opacity: 1;
  }
}

.hero-line {
  width: 100px;
  height: 2px;
  background: linear-gradient(90deg, transparent, #d4af37, transparent);
  margin: 0 auto;
  opacity: 0;
  animation: expandLine 1s ease-out 1s forwards;
}

@keyframes expandLine {
  from {
    width: 0;
    opacity: 0;
  }
  to {
    width: 100px;
    opacity: 1;
  }
}

/* Stats Section */
.stats-section {
  margin-bottom: 3rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
}

.stat-card {
  position: relative;
  background: rgba(255, 255, 255, 0.03);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(212, 175, 55, 0.2);
  border-radius: 20px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  cursor: pointer;
  overflow: hidden;
  animation: cardFloat 0.8s ease-out backwards;
  transition: all 0.4s ease;
}

@keyframes cardFloat {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.stat-card:hover {
  transform: translateY(-5px);
  border-color: rgba(212, 175, 55, 0.4);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.stat-glow {
  position: absolute;
  inset: 0;
  background: radial-gradient(
    circle at var(--x, 50%) var(--y, 50%),
    rgba(212, 175, 55, 0.15) 0%,
    transparent 60%
  );
  opacity: 0;
  transition: opacity 0.3s;
}

.stat-glow.active {
  opacity: 1;
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
  transition: all 0.3s ease;
}

.stat-icon.gold {
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.2) 0%, rgba(212, 175, 55, 0.1) 100%);
  color: #d4af37;
  border: 1px solid rgba(212, 175, 55, 0.3);
}

.stat-icon.success {
  background: linear-gradient(135deg, rgba(40, 167, 69, 0.2) 0%, rgba(40, 167, 69, 0.1) 100%);
  color: #28a745;
  border: 1px solid rgba(40, 167, 69, 0.3);
}

.stat-icon.warning {
  background: linear-gradient(135deg, rgba(255, 193, 7, 0.2) 0%, rgba(255, 193, 7, 0.1) 100%);
  color: #ffc107;
  border: 1px solid rgba(255, 193, 7, 0.3);
}

.stat-icon.info {
  background: linear-gradient(135deg, rgba(23, 162, 184, 0.2) 0%, rgba(23, 162, 184, 0.1) 100%);
  color: #17a2b8;
  border: 1px solid rgba(23, 162, 184, 0.3);
}

.stat-card:hover .stat-icon {
  transform: scale(1.1) rotate(5deg);
}

.stat-number {
  font-size: 2rem;
  font-weight: 700;
  color: #f4e5c2;
  line-height: 1;
  transition: all 0.3s ease;
}

.stat-number.count-up {
  animation: countPulse 0.5s ease;
}

@keyframes countPulse {
  0%,
  100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.2);
    color: #d4af37;
  }
}

.stat-label {
  font-size: 0.9rem;
  color: rgba(244, 229, 194, 0.6);
  margin-top: 0.3rem;
}

.stat-sparkle {
  position: absolute;
  top: 1rem;
  right: 1rem;
  display: flex;
  gap: 0.3rem;
}

.stat-sparkle i {
  color: #d4af37;
  font-size: 0.6rem;
  animation: sparkle 1s ease-in-out infinite;
}

.stat-sparkle i:nth-child(2) {
  animation-delay: 0.2s;
}
.stat-sparkle i:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes sparkle {
  0%,
  100% {
    opacity: 0;
    transform: scale(0);
  }
  50% {
    opacity: 1;
    transform: scale(1);
  }
}

/* Content Section */
.content-section {
  animation: fadeInUp 1s ease-out 0.5s both;
}

/* Footer */
.luxury-footer {
  position: relative;
  z-index: 1;
  padding: 3rem 0;
  border-top: 1px solid rgba(212, 175, 55, 0.1);
  background: rgba(0, 0, 0, 0.3);
}

.footer-content {
  text-align: center;
}

.footer-brand {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.8rem;
  font-family: 'Playfair Display', serif;
  font-size: 1.5rem;
  color: #d4af37;
  margin-bottom: 1rem;
}

.footer-brand i {
  font-size: 1.2rem;
}

.footer-line {
  width: 60px;
  height: 1px;
  background: linear-gradient(90deg, transparent, #d4af37, transparent);
  margin: 0 auto 1rem;
}

.footer-text {
  color: rgba(244, 229, 194, 0.4);
  font-size: 0.9rem;
  letter-spacing: 1px;
}

/* Scrollbar Styling */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.2);
}

::-webkit-scrollbar-thumb {
  background: rgba(212, 175, 55, 0.3);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: rgba(212, 175, 55, 0.5);
}

/* Selection Color */
::selection {
  background: rgba(212, 175, 55, 0.3);
  color: #f4e5c2;
}
</style>
