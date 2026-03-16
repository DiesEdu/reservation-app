<template>
  <div class="luxury-card">
    <div class="card-header-luxury">
      <div class="header-icon">
        <i class="bi bi-calendar-plus"></i>
      </div>
      <div class="header-content">
        <h5 class="mb-0">New Reservation</h5>
        <small>Create an elegant dining experience</small>
      </div>
      <div class="header-glow"></div>
    </div>
    <div class="card-body-luxury">
      <form @submit.prevent="submitForm">
        <div class="row g-4">
          <div class="col-md-6">
            <div class="form-floating-luxury">
              <input
                type="text"
                class="form-control-luxury"
                v-model="form.name"
                required
                placeholder=" "
              />
              <label>Full Name</label>
              <i class="bi bi-person input-icon"></i>
              <div class="input-line"></div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-floating-luxury">
              <input
                type="email"
                class="form-control-luxury"
                v-model="form.email"
                required
                placeholder=" "
              />
              <label>Email Address</label>
              <i class="bi bi-envelope input-icon"></i>
              <div class="input-line"></div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-floating-luxury">
              <input
                type="tel"
                class="form-control-luxury"
                v-model="form.phone"
                required
                placeholder=" "
              />
              <label>Phone Number</label>
              <i class="bi bi-telephone input-icon"></i>
              <div class="input-line"></div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-floating-luxury">
              <select class="form-control-luxury" v-model="form.guests" required>
                <option v-for="n in 12" :key="n" :value="n">
                  {{ n }} {{ n === 1 ? 'Guest' : 'Guests' }}
                </option>
              </select>
              <label>Number of Guests</label>
              <i class="bi bi-people input-icon"></i>
              <div class="input-line"></div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-floating-luxury">
              <input
                type="date"
                class="form-control-luxury"
                v-model="form.date"
                required
                placeholder=" "
              />
              <label>Reservation Date</label>
              <i class="bi bi-calendar-event input-icon"></i>
              <div class="input-line"></div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-floating-luxury">
              <select class="form-control-luxury" v-model="form.time" required>
                <option v-for="time in timeSlots" :key="time" :value="time">{{ time }}</option>
              </select>
              <label>Preferred Time</label>
              <i class="bi bi-clock input-icon"></i>
              <div class="input-line"></div>
            </div>
          </div>

          <div class="col-12">
            <div class="form-floating-luxury">
              <select class="form-control-luxury" v-model="form.table" required>
                <option value="Window Table">Window Table - City View</option>
                <option value="Booth">VIP Booth</option>
                <option value="Bar Seating">Champagne Bar</option>
                <option value="Private Room A">Private Room A</option>
                <option value="Private Room B">Private Room B</option>
                <option value="Patio">Garden Patio</option>
              </select>
              <label>Seating Preference</label>
              <i class="bi bi-shop input-icon"></i>
              <div class="input-line"></div>
            </div>
          </div>

          <div class="col-12">
            <div class="form-floating-luxury textarea-luxury">
              <textarea
                class="form-control-luxury"
                v-model="form.specialRequests"
                rows="3"
                placeholder=" "
              ></textarea>
              <label>Special Requests & Occasions</label>
              <i class="bi bi-stars input-icon"></i>
              <div class="input-line"></div>
            </div>
          </div>

          <div class="col-12 mt-4">
            <button type="submit" class="btn-submit-luxury" :class="{ loading: isSubmitting }">
              <span class="btn-text">
                <i class="bi bi-check2-circle me-2"></i>Confirm Reservation
              </span>
              <span class="btn-shine"></span>
              <div class="particles" v-if="isSubmitting">
                <span v-for="n in 6" :key="n"></span>
              </div>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useReservationStore } from '../stores/reservations'

const store = useReservationStore()
const isSubmitting = ref(false)

const timeSlots = [
  '17:00',
  '17:30',
  '18:00',
  '18:30',
  '19:00',
  '19:30',
  '20:00',
  '20:30',
  '21:00',
  '21:30',
  '22:00',
  '22:30',
]

const form = reactive({
  name: '',
  email: '',
  phone: '',
  guests: 2,
  date: new Date().toISOString().split('T')[0],
  time: '19:00',
  table: 'Window Table',
  specialRequests: '',
})

const submitForm = async () => {
  isSubmitting.value = true
  await new Promise((resolve) => setTimeout(resolve, 1500))
  store.addReservation({ ...form })
  resetForm()
  isSubmitting.value = false
}

const resetForm = () => {
  form.name = ''
  form.email = ''
  form.phone = ''
  form.guests = 2
  form.date = new Date().toISOString().split('T')[0]
  form.time = '19:00'
  form.table = 'Window Table'
  form.specialRequests = ''
}
</script>

<style scoped>
.luxury-card {
  background: rgba(255, 255, 255, 0.03);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(212, 175, 55, 0.2);
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
  animation: cardEntry 0.8s ease-out;
}

@keyframes cardEntry {
  from {
    opacity: 0;
    transform: translateY(30px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.card-header-luxury {
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.1) 0%, rgba(212, 175, 55, 0.05) 100%);
  padding: 2rem;
  display: flex;
  align-items: center;
  gap: 1.5rem;
  position: relative;
  overflow: hidden;
  border-bottom: 1px solid rgba(212, 175, 55, 0.2);
}

.header-icon {
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, #d4af37 0%, #f4e5c2 100%);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
  color: #0a0a0a;
  box-shadow: 0 10px 30px rgba(212, 175, 55, 0.3);
  animation: iconPulse 2s infinite;
}

@keyframes iconPulse {
  0%,
  100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}

.header-content h5 {
  color: #f4e5c2;
  font-size: 1.4rem;
  font-weight: 600;
  letter-spacing: 1px;
}

.header-content small {
  color: rgba(244, 229, 194, 0.6);
  font-size: 0.85rem;
  letter-spacing: 0.5px;
}

.header-glow {
  position: absolute;
  top: -50%;
  right: -10%;
  width: 200px;
  height: 200px;
  background: radial-gradient(circle, rgba(212, 175, 55, 0.3) 0%, transparent 70%);
  animation: glowRotate 10s linear infinite;
}

@keyframes glowRotate {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.card-body-luxury {
  padding: 2rem;
}

.form-floating-luxury {
  position: relative;
  margin-bottom: 0.5rem;
}

.form-control-luxury {
  width: 100%;
  padding: 1.2rem 1rem 0.6rem 3rem;
  background: rgba(0, 0, 0, 0.2);
  border: 1px solid rgba(212, 175, 55, 0.2);
  border-radius: 12px;
  color: #f4e5c2;
  font-size: 0.95rem;
  transition: all 0.3s ease;
  appearance: none;
}

.form-control-luxury:focus {
  outline: none;
  border-color: #d4af37;
  background: rgba(0, 0, 0, 0.3);
  box-shadow: 0 0 20px rgba(212, 175, 55, 0.2);
}

.form-floating-luxury label {
  position: absolute;
  left: 3rem;
  top: 50%;
  transform: translateY(-50%);
  color: rgba(244, 229, 194, 0.5);
  font-size: 0.9rem;
  transition: all 0.3s ease;
  pointer-events: none;
}

.form-floating-luxury.textarea-luxury label {
  top: 1.5rem;
}

.form-control-luxury:focus + label,
.form-control-luxury:not(:placeholder-shown) + label {
  top: 0.6rem;
  font-size: 0.7rem;
  color: #d4af37;
  font-weight: 600;
}

.input-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: rgba(212, 175, 55, 0.6);
  font-size: 1.1rem;
  transition: all 0.3s ease;
}

.textarea-luxury .input-icon {
  top: 1.2rem;
  transform: none;
}

.form-control-luxury:focus ~ .input-icon {
  color: #d4af37;
  transform: translateY(-50%) scale(1.1);
}

.textarea-luxury .form-control-luxury:focus ~ .input-icon {
  transform: scale(1.1);
}

.input-line {
  position: absolute;
  bottom: 0;
  left: 50%;
  width: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent, #d4af37, transparent);
  transition: all 0.4s ease;
  transform: translateX(-50%);
}

.form-control-luxury:focus ~ .input-line {
  width: 100%;
}

.btn-submit-luxury {
  width: 100%;
  padding: 1.2rem;
  background: linear-gradient(135deg, #d4af37 0%, #f4e5c2 50%, #d4af37 100%);
  background-size: 200% 200%;
  border: none;
  border-radius: 12px;
  color: #0a0a0a;
  font-weight: 700;
  font-size: 1.1rem;
  letter-spacing: 1px;
  position: relative;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.4s ease;
  box-shadow: 0 10px 30px rgba(212, 175, 55, 0.3);
}

.btn-submit-luxury:hover {
  transform: translateY(-3px);
  box-shadow: 0 15px 40px rgba(212, 175, 55, 0.5);
  background-position: 100% 100%;
}

.btn-shine {
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
  transition: left 0.5s ease;
}

.btn-submit-luxury:hover .btn-shine {
  left: 100%;
}

.btn-submit-luxury.loading {
  pointer-events: none;
  opacity: 0.8;
}

.particles span {
  position: absolute;
  width: 6px;
  height: 6px;
  background: #d4af37;
  border-radius: 50%;
  animation: particle 1s ease-out infinite;
}

.particles span:nth-child(1) {
  left: 20%;
  animation-delay: 0s;
}
.particles span:nth-child(2) {
  left: 35%;
  animation-delay: 0.1s;
}
.particles span:nth-child(3) {
  left: 50%;
  animation-delay: 0.2s;
}
.particles span:nth-child(4) {
  left: 65%;
  animation-delay: 0.3s;
}
.particles span:nth-child(5) {
  left: 80%;
  animation-delay: 0.4s;
}
.particles span:nth-child(6) {
  left: 95%;
  animation-delay: 0.5s;
}

@keyframes particle {
  0% {
    transform: translateY(0) scale(0);
    opacity: 1;
  }
  100% {
    transform: translateY(-30px) scale(1);
    opacity: 0;
  }
}

select.form-control-luxury {
  cursor: pointer;
  padding-right: 2.5rem;
}

select.form-control-luxury option {
  background: #1a1a1a;
  color: #f4e5c2;
}
</style>
