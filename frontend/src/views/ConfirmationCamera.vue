<template>
  <div class="customer-confirmation">
    <!-- Access Denied Message -->
    <div v-if="accessDenied" class="access-denied">
      <div class="access-denied-card">
        <i class="bi bi-shield-exclamation"></i>
        <h2>Access Restricted</h2>
        <p>You don't have permission to access this page.</p>
        <p class="access-note">Only admin and staff roles can access the confirmation page.</p>
        <button @click="$router.push('/')" class="btn-home">
          <i class="bi bi-house"></i>
          <span>Go to Home</span>
        </button>
      </div>
    </div>

    <!-- Background Animation -->
    <div v-else class="bg-animation">
      <div class="bg-gradient"></div>
      <div class="particles-container">
        <span v-for="n in 15" :key="n" :style="particleStyle(n)"></span>
      </div>
    </div>

    <div class="container">
      <div class="confirmation-wrapper">
        <!-- Header -->
        <!-- <div class="confirmation-header">
          <div class="logo-section">
            <img src="/new-iforte.png" alt="Resonanz Logo" class="logo-icon" />
          </div>
          <p class="subtitle">Reservation Confirmation</p>
        </div> -->

        <section class="hero-section">
          <div class="hero-content">
            <h1 class="hero-title">
              <span class="title-line">Halal Bihalal</span>
              <span class="title-line"
                ><img src="/connected-in-harmony.png" alt="Resonanz Logo" class="logo-icon"
              /></span>
            </h1>
            <p class="hero-subtitle">
              bersama
              <span><img src="/new-iforte.png" alt="Resonanz Logo" class="logo-icon" /></span>
            </p>
            <div class="hero-line"></div>
          </div>
        </section>

        <!-- QR Scanner Section -->
        <div v-if="!reservationData" class="scanner-section">
          <div class="scanner-card">
            <div class="scanner-header">
              <i class="bi bi-qr-code-scan"></i>
              <h2>Scan Your QR Code</h2>
              <p>Please scan the QR code from your reservation confirmation</p>
            </div>

            <!-- QR Scanner -->
            <div class="scanner-container">
              <div v-show="!scannerActive" class="scanner-placeholder">
                <button @click="startScanner" class="btn-start-scan">
                  <i class="bi bi-camera"></i>
                  <span>Start Camera</span>
                </button>
              </div>
              <div v-show="scannerActive">
                <div id="qr-reader" class="qr-reader"></div>
                <button @click="stopScanner" class="btn-stop-scan">
                  <i class="bi bi-x-circle"></i>
                  <span>Stop Scanner</span>
                </button>
              </div>
            </div>

            <!-- Error Message -->
            <div v-if="errorMessage" class="alert-error">
              <i class="bi bi-exclamation-triangle"></i>
              <span>{{ errorMessage }}</span>
            </div>
          </div>
        </div>

        <!-- Reservation Details Section -->
        <div v-else class="details-section">
          <!-- Prominent Table Display -->
          <div class="table-highlight">
            <div class="table-number">
              <span class="table-label">Table</span>
              <span class="table-value">{{ reservationData.table }}</span>
            </div>
            <div class="guest-name">
              <span class="name-label">Guest</span>
              <span class="name-value">{{ reservationData.name }}</span>
            </div>
            <div class="wrap-company-position">
              <div class="guest-name">
                <span class="name-label">Company</span>
                <span class="subname-value">{{ reservationData.company }}</span>
              </div>
              <div class="guest-name">
                <span class="name-label">Position</span>
                <span class="subname-value">{{ reservationData.position }}</span>
              </div>
            </div>
            <div v-if="verifiedAt" class="verified-info">
              <i class="bi bi-check-circle-fill"></i>
              <span>Verified at {{ formatVerifiedDate(verifiedAt) }}</span>
            </div>
          </div>

          <div class="success-animation">
            <div class="checkmark-circle" :class="{ 'already-verified': alreadyVerified }">
              <i :class="alreadyVerified ? 'bi bi-exclamation-lg' : 'bi bi-check-lg'"></i>
            </div>
            <h2 class="success-title">
              {{ alreadyVerified ? 'Reservation Already Verified' : 'Reservation Confirmed!' }}
            </h2>
            <p class="success-subtitle">
              {{
                alreadyVerified
                  ? 'This reservation was previously verified'
                  : "We're excited to welcome you"
              }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Countdown Timer -->
    <div v-if="countdown > 0" class="countdown-timer">
      <i class="bi bi-clock-history"></i>
      <span
        >Returning to scan in <strong>{{ countdown }}</strong> seconds</span
      >
    </div>

    <!-- Scan QR Code Hint - Fixed Middle Right -->
    <div class="scan-hint-fixed">
      <div class="scan-hint-content">
        <span>Scan Here</span>
      </div>
      <div class="direct-arrow">
        <img src="../assets/gif/arrow-direct.gif" style="width: 5rem" alt="Arrow Direction" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onUnmounted, onMounted, nextTick } from 'vue'
import { Html5Qrcode } from 'html5-qrcode'
import { useAuthStore } from '@/stores/auth'

// API Base URL
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

// Auth store
const authStore = useAuthStore()

// Check access permission - only admin and staff can access
const canAccess = computed(() => authStore.canAccessConfirmation)
const accessDenied = ref(false)

// Redirect if no access
onMounted(async () => {
  await authStore.initializeAuth()
  if (!canAccess.value) {
    accessDenied.value = true
  } else {
    // Auto-start camera when page opens
    await startScanner()
  }
})

// State
const scannerActive = ref(false)
const manualCode = ref('')
const reservationData = ref(null)
const reservationCode = ref('')
const errorMessage = ref('')
const loading = ref(false)
// const copied = ref(false)
const alreadyVerified = ref(false)
const verifiedAt = ref(null)
const countdown = ref(0)
let countdownTimer = null
let html5QrCode = null

// Methods
const startScanner = async () => {
  try {
    errorMessage.value = ''
    scannerActive.value = true // Set to true FIRST so element is visible

    // Wait for DOM to update
    await new Promise((resolve) => setTimeout(resolve, 100))

    html5QrCode = new Html5Qrcode('qr-reader')

    const config = {
      fps: 10,
      qrbox: { width: 250, height: 250 },
      aspectRatio: 1.0,
    }

    await html5QrCode.start({ facingMode: 'user' }, config, onScanSuccess, onScanFailure)
  } catch (err) {
    console.error('Error starting scanner:', err)
    errorMessage.value = 'Unable to access camera. Please check permissions or enter code manually.'
    scannerActive.value = false // Reset on error
  }
}

const stopScanner = async () => {
  if (html5QrCode) {
    try {
      await html5QrCode.stop()
      scannerActive.value = false
    } catch (err) {
      console.error('Error stopping scanner:', err)
    }
  }
}

const onScanSuccess = (decodedText) => {
  // Play beep sound on successful scan
  playBeep()
  stopScanner()
  verifyReservation(decodedText)
}

const playBeep = () => {
  try {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)()
    const oscillator = audioContext.createOscillator()
    const gainNode = audioContext.createGain()

    oscillator.connect(gainNode)
    gainNode.connect(audioContext.destination)

    oscillator.frequency.value = 1000 // 1000 Hz beep frequency
    oscillator.type = 'sine'

    gainNode.gain.setValueAtTime(0.3, audioContext.currentTime)
    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2)

    oscillator.start(audioContext.currentTime)
    oscillator.stop(audioContext.currentTime + 0.5)
  } catch (err) {
    console.error('Error playing beep sound:', err)
  }
}

const onScanFailure = () => {
  // Ignore scan failures (they happen continuously while scanning)
}

const verifyReservation = async (code) => {
  loading.value = true
  errorMessage.value = ''

  try {
    // Validate code format (RES-{id}-{timestamp})
    const parts = code.split('-')
    if (parts.length < 2 || parts[0] !== 'RES') {
      throw new Error('Invalid reservation code format')
    }

    // Call verification API
    const response = await fetch(`${API_URL}/reservations/verify`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        qrCode: code,
        method: scannerActive.value ? 'qr_scan' : 'manual_entry',
      }),
    })

    const result = await response.json()

    if (!response.ok || !result.success) {
      throw new Error(result.error || 'Failed to verify reservation')
    }

    // Set reservation data
    reservationData.value = result.data
    reservationCode.value = code
    manualCode.value = ''

    // Start countdown timer to return to scan screen after 5 seconds
    startCountdown()

    // Check if reservation status is confirmed
    if (result.data.status !== 'confirmed') {
      let statusMessage = ''
      if (result.data.status === 'pending') {
        statusMessage =
          'Your reservation is still pending confirmation. Please wait for confirmation before verifying.'
      } else if (result.data.status === 'cancelled') {
        statusMessage = 'Your reservation has been cancelled. Please contact us for assistance.'
      } else {
        statusMessage = 'Your reservation is not confirmed. Status: ' + result.data.status
      }
      throw new Error(statusMessage)
    }

    // Check if already verified and set state
    alreadyVerified.value = result.data.alreadyVerified || false
    verifiedAt.value = result.data.verifiedAt || null
  } catch (error) {
    startScanner()
    console.error('Verification error:', error)
    errorMessage.value = error.message || 'Failed to verify reservation. Please try again.'
    // Clear and focus the input field when there's an error
    manualCode.value = ''
    if (manualCodeInput.value) {
      manualCodeInput.value.focus()
    }
  } finally {
    loading.value = false
  }
}

const resetScanner = async () => {
  // Clear countdown timer if active
  if (countdownTimer) {
    clearInterval(countdownTimer)
    countdownTimer = null
  }
  countdown.value = 0
  reservationData.value = null
  reservationCode.value = ''
  errorMessage.value = ''
  manualCode.value = ''
  alreadyVerified.value = false
  verifiedAt.value = null

  // Focus the manual code input when returning to scan screen
  nextTick(() => {
    if (manualCodeInput.value) {
      manualCodeInput.value.focus()
    }
  })

  // Auto-restart camera after reset
  await startScanner()
}

// const formatDate = (dateString) => {
//   const date = new Date(dateString)
//   return date.toLocaleDateString('en-US', {
//     weekday: 'long',
//     year: 'numeric',
//     month: 'long',
//     day: 'numeric',
//   })
// }

const formatVerifiedDate = (dateString) => {
  if (!dateString) return 'Unknown date'
  const date = new Date(dateString)
  return date.toLocaleString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const particleStyle = () => ({
  left: `${Math.random() * 100}%`,
  animationDelay: `${Math.random() * 20}s`,
  animationDuration: `${15 + Math.random() * 10}s`,
  opacity: Math.random() * 0.5 + 0.1,
})

const startCountdown = () => {
  countdown.value = 5
  countdownTimer = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) {
      clearInterval(countdownTimer)
      resetScanner()
    }
  }, 1000)
}

const manualCodeInput = ref(null)

// Cleanup
onMounted(() => {
  // Auto-focus the manual code input when component mounts
  if (manualCodeInput.value) {
    manualCodeInput.value.focus()
  }
})
onUnmounted(() => {
  if (html5QrCode && scannerActive.value) {
    stopScanner()
  }
  if (countdownTimer) {
    clearInterval(countdownTimer)
  }
})
</script>

<style scoped>
/* Hero Section */
.hero-section {
  text-align: center;
  margin-bottom: 1rem;
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
  font-size: clamp(2.5rem, 6vw, 2.5rem);
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

.title-line img {
  width: 45%;
  height: auto;
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

.hero-subtitle span {
  height: 30px;
}

.hero-subtitle span img {
  width: 10%;
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

.customer-confirmation {
  min-height: 100vh;
  background: var(--bg);
  color: var(--primary-dark);
  font-family: 'Poppins', 'Inter', sans-serif;
  position: relative;
  overflow-x: hidden;
  padding: 3rem 0 3rem; /* offset fixed Navbar */
}

/* Access Denied */
.access-denied {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.access-denied-card {
  background: #ffffff;
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 2.4rem;
  text-align: center;
  max-width: 420px;
  box-shadow: var(--shadow);
}

.access-denied-card i {
  font-size: 3.2rem;
  color: var(--accent);
  margin-bottom: 1rem;
}

.access-denied-card h2 {
  font-size: 1.9rem;
  margin-bottom: 0.5rem;
}
.access-denied-card p {
  color: #5b6b86;
  margin-bottom: 0.3rem;
}
.access-note {
  font-size: 0.9rem;
  color: #9aa5bc;
  margin-bottom: 1rem;
}

.btn-home {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, var(--accent) 0%, #ff9f43 45%, var(--primary) 100%);
  color: #0f172a;
  border: none;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 10px 22px rgba(31, 79, 163, 0.18);
}
.btn-home:hover {
  transform: translateY(-2px);
}

.countdown-timer {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.7) 0%, rgba(170, 138, 46, 0.7) 100%);
  color: #0f172a;
  padding: 0.75rem 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-size: 1rem;
  z-index: 5;
  box-shadow: 0 8px 20px rgba(31, 79, 163, 0.15);
}
.countdown-timer strong {
  font-size: 1.25rem;
  min-width: 1.5rem;
  text-align: center;
}

/* Scan Hint Fixed - Middle Right */
.scan-hint-fixed {
  position: fixed;
  right: 10px;
  top: 46%;
  transform: translateY(-50%);
  z-index: 10;
  animation: pulseHint 2s ease-in-out infinite;
}

@keyframes pulseHint {
  0%,
  100% {
    opacity: 1;
  }
  30% {
    opacity: 0.7;
  }
}

.scan-hint-content {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  border: 2px solid;
  background-color: rgba(255, 216, 77, 0.5);
  border-color: #ffd84d;
  color: #0f172a;
  padding: 0.75rem 1.25rem;
  border-radius: 12px;
  box-shadow: 0 8px 20px rgba(31, 79, 163, 0.25);
  font-weight: 600;
  font-size: 0.95rem;
  white-space: nowrap;
}

.direct-arrow {
  margin-top: 0;
  transform: rotate(180deg);
  display: flex;
  justify-content: start;
}
/* Local background (kept subtle and non-blocking) */
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
    radial-gradient(circle at 85% 18%, rgba(246, 196, 0, 0.16) 0%, transparent 38%),
    radial-gradient(circle at 20% 80%, rgba(255, 107, 107, 0.12) 0%, transparent 35%),
    radial-gradient(circle at 82% 75%, rgba(108, 92, 231, 0.12) 0%, transparent 38%);
  animation: bgShift 20s ease-in-out infinite;
}
@keyframes bgShift {
  0%,
  100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.08);
  }
}
.particles-container span {
  position: absolute;
  width: 4px;
  height: 4px;
  border-radius: 50%;
  animation: float linear infinite;
  mix-blend-mode: multiply;
  background: var(--accent);
}
@keyframes float {
  0% {
    transform: translateY(100vh) scale(0);
    opacity: 0;
  }
  10%,
  90% {
    opacity: 1;
  }
  100% {
    transform: translateY(-100vh) scale(1.4);
    opacity: 0;
  }
}

.container {
  position: relative;
  z-index: 1;
  max-width: 960px;
  margin: 0 auto;
  padding: 0 1.2rem;
}

.confirmation-wrapper {
  animation: fadeInUp 0.8s ease-out;
}
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(24px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Header */
.confirmation-header {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
  margin-bottom: 2.5rem;
}
.logo-section {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 90px;
  width: 180px;
  gap: 0.9rem;
  margin-bottom: 0.75rem;
}

.logo-section img {
  width: 100%;
  object-fit: contain;
}

.logo-section i {
  font-size: 2.4rem;
  color: var(--accent);
  animation: pulse 2s ease-in-out infinite;
}
@keyframes pulse {
  0%,
  100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.06);
  }
}
.logo-section h1 {
  font-family: 'Playfair Display', serif;
  font-size: 1.9rem;
  font-weight: 700;
  color: var(--primary-dark);
  letter-spacing: 2px;
}
.subtitle {
  font-size: 1rem;
  color: #5b6b86;
  letter-spacing: 2px;
  text-transform: uppercase;
}

/* Scanner */
.scanner-section {
  animation: slideIn 0.6s ease-out;
}
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(-12px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.scanner-card {
  background: #ffffff;
  border: 1px solid var(--border);
  border-radius: 18px;
  padding: 2.1rem;
  box-shadow: var(--shadow);
}
.scanner-header {
  text-align: center;
  margin-bottom: 1.6rem;
}
.scanner-header i {
  font-size: 2.8rem;
  color: var(--primary);
  margin-bottom: 0.6rem;
  display: block;
}
.scanner-header h2 {
  font-family: 'Playfair Display', serif;
  font-size: 1.7rem;
  margin-bottom: 0.35rem;
  color: var(--primary-dark);
}
.scanner-header p {
  color: #5b6b86;
  font-size: 0.95rem;
}

.scanner-container {
  margin-bottom: 1.6rem;
}
.scanner-placeholder {
  text-align: center;
  padding: 2.6rem 2rem;
  background: #f6f8ff;
  border: 2px dashed var(--border);
  border-radius: 14px;
}
.btn-start-scan {
  background: linear-gradient(135deg, var(--accent) 0%, #ffd84d 100%);
  color: #0f172a;
  border: none;
  padding: 0.9rem 2.1rem;
  border-radius: 12px;
  font-size: 1.05rem;
  font-weight: 700;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.65rem;
  box-shadow: 0 12px 24px rgba(31, 79, 163, 0.16);
}
.btn-start-scan:hover {
  transform: translateY(-2px);
  box-shadow: 0 16px 30px rgba(31, 79, 163, 0.22);
}
.btn-start-scan i {
  font-size: 1.2rem;
}

.qr-reader {
  width: 100%;
  max-width: 500px;
  margin: 0 auto;
  border-radius: 14px;
  overflow: hidden;
  border: 2px solid var(--border);
  background: #000;
}
.qr-reader video {
  width: 100%;
  height: auto;
  display: block;
  transform: scaleX(-1);
}

.btn-stop-scan {
  width: 100%;
  margin-top: 1rem;
  background: rgba(255, 107, 107, 0.14);
  color: #c92c3a;
  border: 1px solid rgba(255, 107, 107, 0.35);
  padding: 0.75rem;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}
.btn-stop-scan:hover {
  background: rgba(255, 107, 107, 0.2);
}

/* Manual entry */
.manual-entry {
  margin-top: 1.6rem;
  margin-bottom: 1.6rem;
}
.divider {
  text-align: center;
  position: relative;
  margin-bottom: 1.3rem;
}
.divider::before,
.divider::after {
  content: '';
  position: absolute;
  top: 50%;
  width: 40%;
  height: 1px;
  background: var(--border);
}
.divider::before {
  left: 0;
}
.divider::after {
  right: 0;
}
.divider span {
  background: #ffffff;
  padding: 0.4rem 0.9rem;
  color: #8a97af;
  font-size: 0.85rem;
  letter-spacing: 1px;
}

.manual-form {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}
.input-group {
  position: relative;
  display: flex;
  align-items: center;
}
.input-group i {
  position: absolute;
  left: 1rem;
  color: var(--primary);
  font-size: 1.1rem;
}
.form-control {
  width: 100%;
  padding: 0.95rem 1rem 0.95rem 3rem;
  background: #f6f8ff;
  border: 1px solid var(--border);
  border-radius: 12px;
  color: var(--primary-dark);
  font-size: 1rem;
  transition: all 0.3s ease;
}
.form-control:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(31, 79, 163, 0.12);
  background: #ffffff;
}
.form-control::placeholder {
  color: #9aa5bc;
}

.btn-verify {
  background: linear-gradient(135deg, var(--accent) 0%, #ff9f43 45%, var(--primary) 100%);
  color: #0f172a;
  border: none;
  padding: 0.95rem;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 14px 26px rgba(31, 79, 163, 0.18);
}
.btn-verify:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 16px 34px rgba(31, 79, 163, 0.22);
}
.btn-verify:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.spinner {
  animation: spin 1s linear infinite;
}
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.alert-error {
  margin-top: 1.2rem;
  padding: 1rem;
  background: rgba(255, 107, 107, 0.1);
  border: 1px solid rgba(255, 107, 107, 0.3);
  border-radius: 12px;
  color: #c92c3a;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.alert-error i {
  font-size: 1.2rem;
}

/* Details */
.details-section {
  animation: slideIn 0.6s ease-out;
}
.success-animation {
  text-align: center;
  margin-bottom: 1.8rem;
}
.checkmark-circle {
  width: 48px;
  height: 48px;
  margin: 0 auto 1.2rem;
  background: rgba(126, 217, 87, 0.18);
  border: 3px solid #28a745;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.checkmark-circle.already-verified {
  background: rgba(246, 196, 0, 0.2);
  border-color: #f59e0b;
}
.checkmark-circle i {
  font-size: 2.6rem;
  color: #1f7a2f;
}
.checkmark-circle.already-verified i {
  color: #b87400;
}

.success-title {
  font-family: 'Playfair Display', serif;
  font-size: 1.8rem;
  color: var(--primary-dark);
  margin-bottom: 0.4rem;
}
.success-subtitle {
  color: #5b6b86;
  font-size: 1rem;
}

.table-highlight {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem 1.5rem;
  margin: 1.2rem auto 1.6rem;
  background: linear-gradient(145deg, rgba(31, 79, 163, 0.08) 0%, rgba(246, 196, 0, 0.12) 100%);
  border: 1px solid var(--border);
  border-radius: 18px;
  box-shadow: var(--shadow);
}
.table-number {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 1rem;
}
.table-label {
  font-size: 0.95rem;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  color: #5b6b86;
  margin-bottom: 0.4rem;
}
.table-value {
  font-family: 'Playfair Display', serif;
  font-size: 15rem;
  font-weight: 800;
  color: var(--accent);
  line-height: 1;
}

.guest-name {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0.9rem 1.4rem;
  background: #f6f8ff;
  border-radius: 12px;
  margin-bottom: 0.8rem;
  width: 100%;
}
.wrap-company-position {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  width: 100%;
}
.name-label {
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: #8a97af;
  margin-bottom: 0.2rem;
}
.name-value {
  font-family: 'Playfair Display', serif;
  font-size: 1.6rem;
  color: var(--primary-dark);
  font-weight: 700;
}
.subname-value {
  font-family: 'Playfair Display', serif;
  font-size: 1.1rem;
  color: #5b6b86;
  font-weight: 500;
}

.verified-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: #1f7a2f;
  padding: 0.5rem 1rem;
  background: rgba(126, 217, 87, 0.18);
  border-radius: 20px;
}

/* Details Card */
.details-card {
  background: #ffffff;
  border: 1px solid var(--border);
  border-radius: 18px;
  padding: 2rem;
  box-shadow: var(--shadow);
  margin-bottom: 1.6rem;
}
.detail-section {
  margin-bottom: 1.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--border);
}
.detail-section:last-child {
  margin-bottom: 0;
  padding-bottom: 0;
  border-bottom: none;
}
.section-title {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  font-size: 1.2rem;
  color: var(--primary-dark);
  margin-bottom: 1.2rem;
  font-family: 'Playfair Display', serif;
}
.section-title i {
  font-size: 1.3rem;
  color: var(--accent);
}
.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
  gap: 1.2rem;
}
.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}
.detail-item.highlight {
  background: #f6f8ff;
  padding: 0.9rem;
  border-radius: 12px;
  border: 1px solid var(--border);
}
.detail-label {
  font-size: 0.85rem;
  color: #8a97af;
  text-transform: uppercase;
  letter-spacing: 1px;
}
.detail-value {
  font-size: 1.05rem;
  color: var(--primary-dark);
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.special-requests {
  background: #f9fbff;
  padding: 1.1rem;
  border-radius: 12px;
  border-left: 3px solid var(--primary);
}
.special-requests p {
  color: #42506a;
  line-height: 1.6;
  margin: 0;
}

/* Status badge */
.status-section {
  text-align: center;
  margin-bottom: 1.8rem;
}
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.65rem 1.3rem;
  border-radius: 50px;
  font-weight: 700;
  font-size: 0.95rem;
  text-transform: uppercase;
  letter-spacing: 1px;
}
.status-confirmed {
  background: rgba(126, 217, 87, 0.2);
  color: #1f7a2f;
  border: 2px solid #c3f2c5;
}
.status-pending {
  background: rgba(246, 196, 0, 0.22);
  color: #b87400;
  border: 2px solid #ffe7a6;
}
.status-cancelled {
  background: rgba(255, 107, 107, 0.2);
  color: #c92c3a;
  border: 2px solid #ffd0d5;
}

/* Code section */
.code-section {
  text-align: center;
  padding-top: 1.2rem;
}
.code-label {
  font-size: 0.85rem;
  color: #8a97af;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 0.6rem;
}
.code-display {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  background: #f6f8ff;
  padding: 0.9rem 1.3rem;
  border-radius: 12px;
  border: 1px solid var(--border);
}
.code-value {
  font-family: 'Courier New', monospace;
  font-size: 1.15rem;
  color: var(--primary-dark);
  font-weight: 700;
  letter-spacing: 2px;
}

/* Actions */
.action-buttons {
  display: flex;
  gap: 0.9rem;
  flex-wrap: wrap;
}
.btn-primary,
.btn-secondary {
  flex: 1;
  min-width: 200px;
  padding: 0.9rem 1.4rem;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.65rem;
  border: none;
}
.btn-primary {
  background: linear-gradient(135deg, var(--accent) 0%, #ff9f43 45%, var(--primary) 100%);
  color: #0f172a;
  box-shadow: 0 12px 26px rgba(31, 79, 163, 0.18);
}
.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 16px 34px rgba(31, 79, 163, 0.22);
}
.btn-secondary {
  background: #f6f8ff;
  color: var(--primary-dark);
  border: 1px solid var(--border);
}
.btn-secondary:hover {
  background: #eaf1ff;
}

/* Print */
@media print {
  .bg-animation,
  .scanner-section,
  .action-buttons,
  .countdown-timer {
    display: none !important;
  }
  .customer-confirmation {
    background: white;
    color: black;
  }
  .details-card {
    border: 2px solid var(--primary);
    box-shadow: none;
  }
}

/* Responsive */
@media (max-width: 768px) {
  .scanner-card,
  .details-card {
    padding: 1.5rem;
  }
  .logo-section h1 {
    font-size: 1.5rem;
  }
  .detail-grid {
    grid-template-columns: 1fr;
  }
  .action-buttons {
    flex-direction: column;
  }
  .btn-primary,
  .btn-secondary {
    min-width: 100%;
  }
  .wrap-company-position {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
