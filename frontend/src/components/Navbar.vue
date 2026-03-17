<template>
  <nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="#">
        <div class="logo-icon me-3">
          <i class="bi bi-gem"></i>
        </div>
        <div class="brand-text">
          <span class="brand-main">RESONANZ</span>
          <span class="brand-sub">RESERVE</span>
        </div>
      </a>
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
            <a class="nav-link active" href="#">
              <span class="nav-text">Dashboard</span>
              <span class="nav-line"></span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">
              <span class="nav-text">Reservations</span>
              <span class="nav-line"></span>
            </a>
          </li>
          <li class="nav-item">
            <router-link class="nav-link" to="/analytics">
              <span class="nav-text">Analytics</span>
              <span class="nav-line"></span>
            </router-link>
          </li>
          <li class="nav-item ms-lg-3">
            <button class="btn btn-gold btn-sm">
              <i class="bi bi-person-circle me-2"></i>Admin
            </button>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</template>

<style scoped>
.navbar {
  background: rgba(10, 10, 10, 0.85);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(212, 175, 55, 0.2);
  padding: 1rem 0;
  transition: all 0.4s ease;
  z-index: 1000;
}

.navbar.scrolled {
  background: rgba(10, 10, 10, 0.95);
  padding: 0.5rem 0;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.logo-icon {
  width: 45px;
  height: 45px;
  background: linear-gradient(135deg, #d4af37 0%, #f4e5c2 50%, #d4af37 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #0a0a0a;
  font-size: 1.5rem;
  box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
  animation: shimmer 3s infinite;
}

@keyframes shimmer {
  0%,
  100% {
    box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
  }
  50% {
    box-shadow: 0 4px 30px rgba(212, 175, 55, 0.8);
  }
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
  background: linear-gradient(135deg, #d4af37 0%, #f4e5c2 50%, #d4af37 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.brand-sub {
  font-size: 0.6rem;
  letter-spacing: 4px;
  color: rgba(255, 255, 255, 0.6);
  margin-top: 2px;
}

.nav-link {
  color: rgba(255, 255, 255, 0.8) !important;
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
}

.nav-line {
  position: absolute;
  bottom: 0;
  left: 50%;
  width: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent, #d4af37, transparent);
  transition: all 0.4s ease;
  transform: translateX(-50%);
}

.nav-link:hover .nav-line,
.nav-link.active .nav-line {
  width: 80%;
}

.nav-link:hover .nav-text,
.nav-link.active .nav-text {
  color: #d4af37;
}

.btn-gold {
  background: linear-gradient(135deg, #d4af37 0%, #f4e5c2 50%, #d4af37 100%);
  border: none;
  color: #0a0a0a;
  font-weight: 600;
  padding: 0.6rem 1.5rem;
  border-radius: 25px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
}

.btn-gold:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 25px rgba(212, 175, 55, 0.5);
  color: #0a0a0a;
}
</style>
