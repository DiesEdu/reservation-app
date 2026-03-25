<template>
  <div>
    <main class="main-content">
      <div class="container">
        <!-- Hero Section -->
        <section class="hero-section">
          <div class="hero-content">
            <h1 class="hero-title">
              <span class="title-line">Exquisite</span>
              <span class="title-line">Dining</span>
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
  </div>
</template>

<script setup>
import { computed, reactive, onMounted } from 'vue'
import { useReservationStore } from '../stores/reservations'
import ReservationForm from '../components/ReservationForm.vue'
import ReservationList from '../components/ReservationList.vue'

const store = useReservationStore()

// Fetch reservations on mount
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
</script>

<style scoped>
/* Main Content */
.main-content {
  position: relative;
  z-index: 1;
  padding-top: 100px;
  padding-bottom: 4rem;
  color: var(--primary-dark);
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
  color: var(--primary-dark);
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
  background: linear-gradient(135deg, var(--accent) 0%, #ff9f43 50%, var(--primary) 100%);
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
  color: #5b6b86;
  letter-spacing: 2px;
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
  background: linear-gradient(90deg, transparent, var(--primary), transparent);
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
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 18px;
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  cursor: pointer;
  overflow: hidden;
  animation: cardFloat 0.8s ease-out backwards;
  transition: all 0.4s ease;
  box-shadow: var(--shadow);
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
  border-color: rgba(31, 79, 163, 0.18);
  box-shadow: 0 24px 48px rgba(31, 79, 163, 0.15);
}

.stat-glow {
  position: absolute;
  inset: 0;
  background: radial-gradient(
    circle at var(--x, 50%) var(--y, 50%),
    rgba(246, 196, 0, 0.18) 0%,
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
  box-shadow: inset 0 0 0 2px rgba(255, 255, 255, 0.35);
}

.stat-icon.gold {
  background: linear-gradient(135deg, #ffd84d 0%, #f6c400 100%);
  color: #1b1f3b;
  border: 1px solid #ffe37a;
}

.stat-icon.success {
  background: linear-gradient(135deg, #7ed957 0%, #4fc26a 100%);
  color: #0c4c2c;
  border: 1px solid #c3f2c5;
}

.stat-icon.warning {
  background: linear-gradient(135deg, #ff9f43 0%, #ff6b6b 100%);
  color: #611316;
  border: 1px solid #ffd0b0;
}

.stat-icon.info {
  background: linear-gradient(135deg, #8cc4ff 0%, #1f4fa3 100%);
  color: #0f172a;
  border: 1px solid #cfe3ff;
}

.stat-card:hover .stat-icon {
  transform: scale(1.1) rotate(5deg);
}

.stat-number {
  font-size: 2rem;
  font-weight: 700;
  color: var(--primary-dark);
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
    color: var(--accent);
  }
}

.stat-label {
  font-size: 0.9rem;
  color: #5b6b86;
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
  color: var(--accent);
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
</style>
