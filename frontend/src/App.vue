<template>
  <div class="luxury-app">
    <!-- Animated Background -->
    <div class="bg-animation">
      <div class="bg-gradient"></div>
      <div class="particles-container">
        <span v-for="n in 20" :key="n" :style="particleStyle()"></span>
      </div>
    </div>

    <Navbar v-if="$route.name === 'home'" />

    <router-view />

    <!-- Footer -->
    <footer v-if="$route.name === 'home'" class="luxury-footer">
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
import Navbar from './components/Navbar.vue'

const particleStyle = () => ({
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

/* Footer */
.luxury-footer {
  position: relative;
  z-index: 1;
  padding: 3rem 0;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.5), transparent);
  border-top: 1px solid rgba(212, 175, 55, 0.1);
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
  font-weight: 600;
  letter-spacing: 2px;
  color: #d4af37;
  margin-bottom: 1rem;
}

.footer-brand i {
  font-size: 1.5rem;
}

.footer-line {
  width: 60px;
  height: 1px;
  background: linear-gradient(90deg, transparent, #d4af37, transparent);
  margin: 1rem auto;
}

.footer-text {
  color: rgba(244, 229, 194, 0.5);
  font-size: 0.9rem;
  letter-spacing: 1px;
}
</style>
