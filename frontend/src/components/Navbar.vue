<template>
  <nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
      <router-link class="navbar-brand d-flex align-items-center" to="/">
        <!-- <div class="logo-icon me-3">
          <i class="bi bi-gem"></i>
        </div>
        <div class="brand-text">
          <span class="brand-main">RESONANZ</span>
          <span class="brand-sub">RESERVE</span>
        </div> -->
        <div class="logo-icon me-3">
          <img src="/new-iforte.png" alt="Logo" class="logo-image" />
        </div>
      </router-link>
      <button
        class="navbar-toggler border-0"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#navbarNav"
      >
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto align-items-center">
          <li class="nav-item">
            <router-link class="nav-link" to="/">
              <span class="nav-text">Home</span>
              <span class="nav-line"></span>
            </router-link>
          </li>
          <li class="nav-item">
            <router-link class="nav-link" to="/analytics">
              <span class="nav-text">Analytics</span>
              <span class="nav-line"></span>
            </router-link>
          </li>
          <li class="nav-item ms-lg-3">
            <!-- If not authenticated, show login button -->
            <template v-if="!isAuthenticated">
              <router-link to="/login" class="btn btn-gold btn-sm">
                <i class="bi bi-person-circle me-2"></i>Login
              </router-link>
            </template>
            <!-- If authenticated, show user name and confirmation button -->
            <template v-else>
              <div class="d-flex align-items-center gap-2">
                <router-link to="/confirm-scanner" class="btn btn-gold btn-sm">
                  <i class="bi bi-check2-circle me-2"></i>QR Scanner
                </router-link>
                <router-link to="/confirm-camera" class="btn btn-gold btn-sm">
                  <i class="bi bi-check2-circle me-2"></i>Camera
                </router-link>
                <div class="dropdown">
                  <button
                    class="btn btn-sm dropdown-toggle user-menu-btn"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                  >
                    <i class="bi bi-person-circle me-2"></i>{{ userName }}
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <span class="dropdown-item-text user-email">{{ userEmail }}</span>
                    </li>
                    <li><hr class="dropdown-divider" /></li>
                    <li>
                      <router-link to="/profile" class="dropdown-item">
                        <i class="bi bi-person me-2"></i>My Profile
                      </router-link>
                    </li>
                    <li>
                      <a class="dropdown-item" href="#" @click.prevent="handleLogout">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                      </a>
                    </li>
                  </ul>
                </div>
              </div>
            </template>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

// Computed properties
const isAuthenticated = computed(() => authStore.isAuthenticated)
const userName = computed(() => authStore.userName)
const userEmail = computed(() => authStore.userEmail)

// Handle logout
const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>

<style scoped>
.navbar {
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border-bottom: 1px solid var(--border);
  padding: 1rem 0;
  transition: all 0.4s ease;
  z-index: 1000;
  box-shadow: 0 12px 30px rgba(31, 79, 163, 0.08);
}

.navbar.scrolled {
  background: rgba(255, 255, 255, 0.98);
  padding: 0.5rem 0;
  box-shadow: 0 18px 40px rgba(31, 79, 163, 0.12);
}

.logo-icon {
  width: 90px;
  height: 45px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--primary-dark);
  font-size: 1.5rem;
}

.logo-icon img {
  width: 100%;
}

.brand-text {
  display: flex;
  flex-direction: column;
  line-height: 1;
}

.brand-main {
  font-size: 1.5rem;
  font-weight: 700;
  letter-spacing: 3px;
  color: var(--primary-dark);
}

.brand-sub {
  font-size: 0.65rem;
  letter-spacing: 4px;
  color: #5b6b86;
  margin-top: 2px;
}

.nav-link {
  color: rgba(21, 52, 109, 0.7) !important;
  padding: 0.5rem 1.2rem !important;
  position: relative;
  overflow: hidden;
}

.nav-text {
  position: relative;
  z-index: 2;
  font-weight: 500;
  letter-spacing: 1px;
  font-size: 0.9rem;
  transition: color 0.3s ease;
  text-transform: uppercase;
}

.nav-line {
  position: absolute;
  bottom: 0;
  left: 50%;
  width: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent, var(--accent), transparent);
  transition: all 0.4s ease;
  transform: translateX(-50%);
}

.nav-link:hover .nav-line,
.nav-link.active .nav-line {
  width: 80%;
}

.nav-link:hover .nav-text,
.nav-link.active .nav-text {
  color: var(--primary-dark);
}

.btn-gold {
  background: linear-gradient(135deg, var(--accent) 0%, #ffd84d 100%);
  border: none;
  color: #12213f;
  font-weight: 600;
  padding: 0.6rem 1.5rem;
  border-radius: 25px;
  transition: all 0.3s ease;
  box-shadow: 0 8px 18px rgba(246, 196, 0, 0.35);
}

.btn-gold:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 30px rgba(31, 79, 163, 0.18);
  color: #0f172a;
}

/* Dropdown Styles */
.btn-gold.dropdown-toggle::after {
  margin-left: 0.5rem;
}

.user-menu-btn {
  color: var(--primary-dark);
  border: 1px solid var(--primary-dark);
  background: #ffffff;
  transition: all 0.2s ease;
}

.user-menu-btn:hover,
.user-menu-btn:focus {
  color: #ffffff;
  background: var(--primary);
  border-color: var(--primary);
  box-shadow: 0 10px 22px rgba(31, 79, 163, 0.18);
}

.dropdown-menu {
  background: #ffffff;
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 0.5rem;
  margin-top: 0.5rem;
  box-shadow: var(--shadow);
}

.dropdown-item {
  color: var(--primary-dark);
  border-radius: 6px;
  padding: 0.5rem 1rem;
  transition: all 0.2s ease;
}

.dropdown-item:hover {
  background: rgba(31, 79, 163, 0.08);
  color: var(--primary);
}

.dropdown-divider {
  border-color: var(--border);
}

.user-email {
  color: #5b6b86;
  font-size: 0.85rem;
}
</style>
