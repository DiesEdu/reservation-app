<template>
  <div class="customer-confirmation">
    <!-- Background Animation -->
    <div class="bg-animation">
      <div class="bg-gradient"></div>
      <div class="particles-container">
        <span v-for="n in 15" :key="n" :style="particleStyle(n)"></span>
      </div>
    </div>

    <div class="container">
      <div class="confirmation-wrapper">
        <!-- Header -->
        <div class="confirmation-header">
          <div class="logo-section">
            <i class="bi bi-gem"></i>
            <h1>LUXE RESERVE</h1>
          </div>
          <p class="subtitle">Reservation Confirmation</p>
        </div>

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

            <!-- Manual Entry Option -->
            <div class="manual-entry">
              <div class="divider">
                <span>OR</span>
              </div>
              <form @submit.prevent="verifyManualCode" class="manual-form">
                <div class="input-group">
                  <i class="bi bi-hash"></i>
                  <input
                    v-model="manualCode"
                    type="text"
                    placeholder="Enter reservation code manually"
                    class="form-control"
                  />
                </div>
                <button type="submit" class="btn-verify" :disabled="!manualCode || loading">
                  <span v-if="!loading">Verify Code</span>
                  <span v-else>
                    <i class="bi bi-hourglass-split spinner"></i>
                    Verifying...
                  </span>
                </button>
              </form>
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
          <div class="success-animation">
            <div class="checkmark-circle">
              <i class="bi bi-check-lg"></i>
            </div>
            <h2 class="success-title">Reservation Confirmed!</h2>
            <p class="success-subtitle">We're excited to welcome you</p>
          </div>

          <div class="details-card">
            <!-- Guest Information -->
            <div class="detail-section">
              <h3 class="section-title">
                <i class="bi bi-person-circle"></i>
                Guest Information
              </h3>
              <div class="detail-grid">
                <div class="detail-item">
                  <span class="detail-label">Name</span>
                  <span class="detail-value">{{ reservationData.name }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Email</span>
                  <span class="detail-value">{{ reservationData.email }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Phone</span>
                  <span class="detail-value">{{ reservationData.phone }}</span>
                </div>
              </div>
            </div>

            <!-- Reservation Details -->
            <div class="detail-section">
              <h3 class="section-title">
                <i class="bi bi-calendar-event"></i>
                Reservation Details
              </h3>
              <div class="detail-grid">
                <div class="detail-item highlight">
                  <span class="detail-label">Date</span>
                  <span class="detail-value">{{ formatDate(reservationData.date) }}</span>
                </div>
                <div class="detail-item highlight">
                  <span class="detail-label">Time</span>
                  <span class="detail-value">{{ reservationData.time }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Number of Guests</span>
                  <span class="detail-value">
                    <i class="bi bi-people-fill"></i>
                    {{ reservationData.guests }}
                    {{ reservationData.guests === 1 ? 'Guest' : 'Guests' }}
                  </span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Table Preference</span>
                  <span class="detail-value">
                    <i class="bi bi-table"></i>
                    {{ reservationData.table }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Special Requests -->
            <div v-if="reservationData.specialRequests" class="detail-section">
              <h3 class="section-title">
                <i class="bi bi-chat-left-text"></i>
                Special Requests
              </h3>
              <div class="special-requests">
                <p>{{ reservationData.specialRequests }}</p>
              </div>
            </div>

            <!-- Status Badge -->
            <div class="status-section">
              <div class="status-badge" :class="statusClass">
                <i :class="statusIcon"></i>
                <span>{{ statusText }}</span>
              </div>
            </div>

            <!-- Reservation Code -->
            <div class="code-section">
              <p class="code-label">Reservation Code</p>
              <div class="code-display">
                <span class="code-value">{{ reservationCode }}</span>
                <button
                  @click="copyCode"
                  class="btn-copy"
                  :title="copied ? 'Copied!' : 'Copy code'"
                >
                  <i :class="copied ? 'bi bi-check-lg' : 'bi bi-clipboard'"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="action-buttons">
            <button @click="resetScanner" class="btn-secondary">
              <i class="bi bi-arrow-left"></i>
              Scan Another Code
            </button>
            <button @click="printConfirmation" class="btn-primary">
              <i class="bi bi-printer"></i>
              Print Confirmation
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue'
import { Html5Qrcode } from 'html5-qrcode'

// API Base URL
const API_URL = 'http://localhost:8000/api'

// State
const scannerActive = ref(false)
const manualCode = ref('')
const reservationData = ref(null)
const reservationCode = ref('')
const errorMessage = ref('')
const loading = ref(false)
const copied = ref(false)
let html5QrCode = null

// Computed
const statusClass = computed(() => {
  if (!reservationData.value) return ''
  const status = reservationData.value.status
  return {
    confirmed: 'status-confirmed',
    pending: 'status-pending',
    cancelled: 'status-cancelled',
  }[status]
})

const statusIcon = computed(() => {
  if (!reservationData.value) return ''
  const status = reservationData.value.status
  return {
    confirmed: 'bi bi-check-circle-fill',
    pending: 'bi bi-hourglass-split',
    cancelled: 'bi bi-x-circle-fill',
  }[status]
})

const statusText = computed(() => {
  if (!reservationData.value) return ''
  const status = reservationData.value.status
  return {
    confirmed: 'Confirmed',
    pending: 'Pending Confirmation',
    cancelled: 'Cancelled',
  }[status]
})

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

    await html5QrCode.start({ facingMode: 'environment' }, config, onScanSuccess, onScanFailure)
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
  console.log('QR Code scanned:', decodedText)
  stopScanner()
  verifyReservation(decodedText)
}

const onScanFailure = () => {
  // Ignore scan failures (they happen continuously while scanning)
}

const verifyManualCode = () => {
  if (manualCode.value.trim()) {
    verifyReservation(manualCode.value.trim())
  }
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

    // Show message if already verified
    if (result.data.alreadyVerified) {
      console.log('Note: This reservation was already verified at', result.data.verifiedAt)
    }
  } catch (error) {
    console.error('Verification error:', error)
    errorMessage.value = error.message || 'Failed to verify reservation. Please try again.'
  } finally {
    loading.value = false
  }
}

const resetScanner = () => {
  reservationData.value = null
  reservationCode.value = ''
  errorMessage.value = ''
  manualCode.value = ''
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

const copyCode = async () => {
  try {
    await navigator.clipboard.writeText(reservationCode.value)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch (err) {
    console.error('Failed to copy:', err)
  }
}

const printConfirmation = () => {
  window.print()
}

const particleStyle = () => ({
  left: `${Math.random() * 100}%`,
  animationDelay: `${Math.random() * 20}s`,
  animationDuration: `${15 + Math.random() * 10}s`,
  opacity: Math.random() * 0.5 + 0.1,
})

// Cleanup
onUnmounted(() => {
  if (html5QrCode && scannerActive.value) {
    stopScanner()
  }
})
</script>

<style scoped>
.customer-confirmation {
  min-height: 100vh;
  background: #0a0a0a;
  color: #f4e5c2;
  font-family: 'Inter', sans-serif;
  position: relative;
  overflow-x: hidden;
  padding: 2rem 0;
}

/* Background Animation */
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
    radial-gradient(ellipse at 80% 80%, rgba(212, 175, 55, 0.1) 0%, transparent 50%);
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

/* Container */
.container {
  position: relative;
  z-index: 1;
  max-width: 900px;
  margin: 0 auto;
  padding: 0 1rem;
}

.confirmation-wrapper {
  animation: fadeInUp 0.8s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Header */
.confirmation-header {
  text-align: center;
  margin-bottom: 3rem;
}

.logo-section {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.logo-section i {
  font-size: 2.5rem;
  color: #d4af37;
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%,
  100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
}

.logo-section h1 {
  font-family: 'Playfair Display', serif;
  font-size: 2rem;
  font-weight: 700;
  background: linear-gradient(135deg, #d4af37 0%, #f4e5c2 50%, #d4af37 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: 3px;
}

.subtitle {
  font-size: 1rem;
  color: rgba(244, 229, 194, 0.6);
  letter-spacing: 2px;
  text-transform: uppercase;
}

/* Scanner Section */
.scanner-section {
  animation: slideIn 0.6s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.scanner-card {
  background: rgba(255, 255, 255, 0.03);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(212, 175, 55, 0.2);
  border-radius: 24px;
  padding: 2.5rem;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
}

.scanner-header {
  text-align: center;
  margin-bottom: 2rem;
}

.scanner-header i {
  font-size: 3rem;
  color: #d4af37;
  margin-bottom: 1rem;
  display: block;
}

.scanner-header h2 {
  font-family: 'Playfair Display', serif;
  font-size: 1.8rem;
  margin-bottom: 0.5rem;
  color: #f4e5c2;
}

.scanner-header p {
  color: rgba(244, 229, 194, 0.6);
  font-size: 0.95rem;
}

/* Scanner Container */
.scanner-container {
  margin-bottom: 2rem;
  min-height: 300px;
}

.scanner-placeholder {
  text-align: center;
  padding: 3rem 2rem;
  background: rgba(0, 0, 0, 0.3);
  border: 2px dashed rgba(212, 175, 55, 0.3);
  border-radius: 16px;
}

.btn-start-scan {
  background: linear-gradient(135deg, #d4af37 0%, #c9a02c 100%);
  color: #0a0a0a;
  border: none;
  padding: 1rem 2.5rem;
  border-radius: 12px;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 20px rgba(212, 175, 55, 0.3);
}

.btn-start-scan:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 30px rgba(212, 175, 55, 0.5);
}

.btn-start-scan i {
  font-size: 1.3rem;
}

.qr-reader {
  width: 100%;
  max-width: 500px;
  margin: 0 auto;
  border-radius: 16px;
  overflow: hidden;
  border: 2px solid rgba(212, 175, 55, 0.3);
  background: #000;
}

.qr-reader video {
  width: 100%;
  height: auto;
  display: block;
}

.btn-stop-scan {
  width: 100%;
  margin-top: 1rem;
  background: rgba(220, 53, 69, 0.2);
  color: #dc3545;
  border: 1px solid rgba(220, 53, 69, 0.3);
  padding: 0.75rem;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
}

.btn-stop-scan:hover {
  background: rgba(220, 53, 69, 0.3);
}

/* Manual Entry */
.manual-entry {
  margin-top: 2rem;
}

.divider {
  text-align: center;
  position: relative;
  margin-bottom: 1.5rem;
}

.divider::before,
.divider::after {
  content: '';
  position: absolute;
  top: 50%;
  width: 40%;
  height: 1px;
  background: rgba(212, 175, 55, 0.2);
}

.divider::before {
  left: 0;
}

.divider::after {
  right: 0;
}

.divider span {
  background: rgba(255, 255, 255, 0.03);
  padding: 0.5rem 1rem;
  color: rgba(244, 229, 194, 0.5);
  font-size: 0.85rem;
  letter-spacing: 1px;
}

.manual-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.input-group {
  position: relative;
  display: flex;
  align-items: center;
}

.input-group i {
  position: absolute;
  left: 1rem;
  color: rgba(212, 175, 55, 0.6);
  font-size: 1.2rem;
}

.form-control {
  width: 100%;
  padding: 1rem 1rem 1rem 3rem;
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(212, 175, 55, 0.2);
  border-radius: 12px;
  color: #f4e5c2;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.form-control:focus {
  outline: none;
  border-color: rgba(212, 175, 55, 0.5);
  box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
}

.form-control::placeholder {
  color: rgba(244, 229, 194, 0.3);
}

.btn-verify {
  background: linear-gradient(135deg, #d4af37 0%, #c9a02c 100%);
  color: #0a0a0a;
  border: none;
  padding: 1rem;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 20px rgba(212, 175, 55, 0.3);
}

.btn-verify:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 30px rgba(212, 175, 55, 0.5);
}

.btn-verify:disabled {
  opacity: 0.5;
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

/* Alert */
.alert-error {
  margin-top: 1.5rem;
  padding: 1rem;
  background: rgba(220, 53, 69, 0.1);
  border: 1px solid rgba(220, 53, 69, 0.3);
  border-radius: 12px;
  color: #dc3545;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  animation: shake 0.5s ease;
}

@keyframes shake {
  0%,
  100% {
    transform: translateX(0);
  }
  25% {
    transform: translateX(-10px);
  }
  75% {
    transform: translateX(10px);
  }
}

.alert-error i {
  font-size: 1.3rem;
}

/* Details Section */
.details-section {
  animation: slideIn 0.6s ease-out;
}

.success-animation {
  text-align: center;
  margin-bottom: 2rem;
}

.checkmark-circle {
  width: 100px;
  height: 100px;
  margin: 0 auto 1.5rem;
  background: linear-gradient(135deg, rgba(40, 167, 69, 0.2) 0%, rgba(40, 167, 69, 0.1) 100%);
  border: 3px solid #28a745;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: scaleIn 0.5s ease-out;
}

@keyframes scaleIn {
  from {
    transform: scale(0);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

.checkmark-circle i {
  font-size: 3rem;
  color: #28a745;
  animation: checkmark 0.5s ease-out 0.3s both;
}

@keyframes checkmark {
  from {
    transform: scale(0) rotate(-45deg);
  }
  to {
    transform: scale(1) rotate(0deg);
  }
}

.success-title {
  font-family: 'Playfair Display', serif;
  font-size: 2rem;
  color: #28a745;
  margin-bottom: 0.5rem;
}

.success-subtitle {
  color: rgba(244, 229, 194, 0.6);
  font-size: 1rem;
}

/* Details Card */
.details-card {
  background: rgba(255, 255, 255, 0.03);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(212, 175, 55, 0.2);
  border-radius: 24px;
  padding: 2.5rem;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
  margin-bottom: 2rem;
}

.detail-section {
  margin-bottom: 2rem;
  padding-bottom: 2rem;
  border-bottom: 1px solid rgba(212, 175, 55, 0.1);
}

.detail-section:last-child {
  margin-bottom: 0;
  padding-bottom: 0;
  border-bottom: none;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.3rem;
  color: #d4af37;
  margin-bottom: 1.5rem;
  font-family: 'Playfair Display', serif;
}

.section-title i {
  font-size: 1.5rem;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.detail-item.highlight {
  background: rgba(212, 175, 55, 0.05);
  padding: 1rem;
  border-radius: 12px;
  border: 1px solid rgba(212, 175, 55, 0.2);
}

.detail-label {
  font-size: 0.85rem;
  color: rgba(244, 229, 194, 0.5);
  text-transform: uppercase;
  letter-spacing: 1px;
}

.detail-value {
  font-size: 1.1rem;
  color: #f4e5c2;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.special-requests {
  background: rgba(0, 0, 0, 0.3);
  padding: 1.25rem;
  border-radius: 12px;
  border-left: 3px solid #d4af37;
}

.special-requests p {
  color: rgba(244, 229, 194, 0.8);
  line-height: 1.6;
  margin: 0;
}

/* Status Section */
.status-section {
  text-align: center;
  margin-bottom: 2rem;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border-radius: 50px;
  font-weight: 600;
  font-size: 1rem;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.status-confirmed {
  background: rgba(40, 167, 69, 0.2);
  color: #28a745;
  border: 2px solid #28a745;
}

.status-pending {
  background: rgba(255, 193, 7, 0.2);
  color: #ffc107;
  border: 2px solid #ffc107;
}

.status-cancelled {
  background: rgba(220, 53, 69, 0.2);
  color: #dc3545;
  border: 2px solid #dc3545;
}

/* Code Section */
.code-section {
  text-align: center;
  padding-top: 1.5rem;
}

.code-label {
  font-size: 0.85rem;
  color: rgba(244, 229, 194, 0.5);
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 0.75rem;
}

.code-display {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  background: rgba(0, 0, 0, 0.3);
  padding: 1rem 1.5rem;
  border-radius: 12px;
  border: 1px solid rgba(212, 175, 55, 0.2);
}

.code-value {
  font-family: 'Courier New', monospace;
  font-size: 1.2rem;
  color: #d4af37;
  font-weight: 600;
  letter-spacing: 2px;
}

.btn-copy {
  background: rgba(212, 175, 55, 0.1);
  border: 1px solid rgba(212, 175, 55, 0.3);
  color: #d4af37;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 1.1rem;
}

.btn-copy:hover {
  background: rgba(212, 175, 55, 0.2);
  transform: scale(1.1);
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.btn-primary,
.btn-secondary {
  flex: 1;
  min-width: 200px;
  padding: 1rem 2rem;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  transition: all 0.3s ease;
  border: none;
}

.btn-primary {
  background: linear-gradient(135deg, #d4af37 0%, #c9a02c 100%);
  color: #0a0a0a;
  box-shadow: 0 4px 20px rgba(212, 175, 55, 0.3);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 30px rgba(212, 175, 55, 0.5);
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.05);
  color: #f4e5c2;
  border: 1px solid rgba(212, 175, 55, 0.3);
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(212, 175, 55, 0.5);
}

/* Print Styles */
@media print {
  .bg-animation,
  .scanner-section,
  .action-buttons {
    display: none !important;
  }

  .customer-confirmation {
    background: white;
    color: black;
  }

  .details-card {
    border: 2px solid #d4af37;
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
}
</style>
