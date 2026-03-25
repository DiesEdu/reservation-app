<template>
  <div class="luxury-app">
    <!-- Animated Background -->
    <div class="bg-animation" aria-hidden="true">
      <div class="bg-gradient"></div>
      <div class="particles-container">
        <span v-for="n in 20" :key="n" :style="particleStyle()"></span>
      </div>
    </div>

    <div class="content-layer">
      <Navbar v-if="showNavbar" />

      <router-view />

      <!-- Footer -->
      <footer v-if="showFooter" class="luxury-footer">
        <div class="container">
          <div class="footer-content">
            <div class="footer-brand">
              <i class="bi bi-gem"></i>
              <span>RESONANZ RESERVE</span>
            </div>
            <div class="footer-line"></div>
            <p class="footer-text">Elevating hospitality through elegant technology</p>
          </div>
        </div>
      </footer>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from './stores/auth'
import Navbar from './components/Navbar.vue'

const route = useRoute()
const authStore = useAuthStore()
const particleColors = ['#1f4fa3', '#f6c400', '#ff6b6b', '#6c5ce7', '#7ed957']

// Initialize auth state on app start
onMounted(async () => {
  await authStore.initializeAuth()
})

const showNavbar = computed(() => {
  return ['home', 'login', 'register'].includes(route.name)
})

const showFooter = computed(() => {
  return route.name === 'home'
})

const particleStyle = () => ({
  left: `${Math.random() * 100}%`,
  animationDelay: `${Math.random() * 20}s`,
  animationDuration: `${15 + Math.random() * 10}s`,
  opacity: Math.random() * 0.5 + 0.1,
  background: particleColors[Math.floor(Math.random() * particleColors.length)],
  boxShadow: `0 0 14px ${particleColors[Math.floor(Math.random() * particleColors.length)]}`,
})
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Poppins:wght@400;500;600;700&display=swap');

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

:root {
  --bg: #fdfcf7;
  --primary: #1f4fa3;
  --primary-dark: #15346d;
  --accent: #f6c400;
  --accent-2: #ff6b6b;
  --accent-3: #6c5ce7;
  --accent-4: #7ed957;
  --muted: #4a4a4a;
  --card: #ffffff;
  --border: #e6eaf5;
  --shadow: 0 15px 40px rgba(31, 79, 163, 0.12);
}

.luxury-app {
  min-height: 100vh;
  background: var(--bg);
  color: #1c1c1c;
  font-family: 'Poppins', 'Inter', sans-serif;
  overflow-x: hidden;
  position: relative;
}

.content-layer {
  position: relative;
  z-index: 1;
}

/* Animated Background */
.bg-animation {
  position: fixed;
  inset: 0;
  z-index: 0;
  overflow: hidden;
  pointer-events: none;
}

.bg-gradient {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(circle at 12% 20%, rgba(31, 79, 163, 0.12) 0%, transparent 35%),
    radial-gradient(circle at 85% 18%, rgba(246, 196, 0, 0.18) 0%, transparent 38%),
    radial-gradient(circle at 20% 80%, rgba(255, 107, 107, 0.12) 0%, transparent 35%),
    radial-gradient(circle at 82% 75%, rgba(108, 92, 231, 0.12) 0%, transparent 38%);
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
  border-radius: 50%;
  animation: float linear infinite;
  mix-blend-mode: multiply;
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

/* Footer */
.luxury-footer {
  position: relative;
  z-index: 1;
  padding: 3rem 0;
  background: linear-gradient(to top, rgba(31, 79, 163, 0.08), transparent);
  border-top: 1px solid var(--border);
}

.footer-content {
  text-align: center;
}

.footer-brand {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  font-size: 1.2rem;
  font-weight: 700;
  letter-spacing: 2px;
  color: var(--primary);
  margin-bottom: 1rem;
}

.footer-brand i {
  font-size: 1.6rem;
  color: var(--accent);
}

.footer-line {
  width: 60px;
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--primary), transparent);
  margin: 1rem auto;
}

.footer-text {
  color: #4d5668;
  font-size: 0.95rem;
  letter-spacing: 0.5px;
}
</style>
