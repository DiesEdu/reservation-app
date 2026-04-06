import { createRouter, createWebHistory } from 'vue-router'
import HomePage from '../views/HomePage.vue'
import ConfirmationCamera from '../views/ConfirmationCamera.vue'
import ConfirmationScanner from '../views/ConfirmationScanner.vue'
import AnalyticsPage from '../views/AnalyticsPage.vue'
import LoginPage from '../views/LoginPage.vue'
import RegisterPage from '../views/RegisterPage.vue'
import ProfilePage from '../views/ProfilePage.vue'
import VerifySuccessPage from '../views/VerifySuccessPage.vue'
import GuestConfirmationPage from '../views/GuestConfirmationPage.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomePage,
      meta: {
        title: 'Luxe Reserve - Exquisite Dining Management',
      },
    },
    {
      path: '/confirm-scanner',
      name: 'customer-confirmation',
      component: ConfirmationScanner,
      meta: {
        title: 'Confirm Reservation',
      },
    },
    {
      path: '/confirm-camera',
      name: 'confirmation-by-camera',
      component: ConfirmationCamera,
      meta: {
        title: 'Confirmation by Camera',
      },
    },
    {
      path: '/analytics',
      name: 'analytics',
      component: AnalyticsPage,
      meta: {
        title: 'Analytics',
      },
    },
    {
      path: '/login',
      name: 'login',
      component: LoginPage,
      meta: {
        title: 'Login',
        guest: true,
      },
    },
    {
      path: '/register',
      name: 'register',
      component: RegisterPage,
      meta: {
        title: 'Register',
        guest: true,
      },
    },
    {
      path: '/profile',
      name: 'profile',
      component: ProfilePage,
      meta: {
        title: 'My Profile',
        requiresAuth: true,
      },
    },
    {
      path: '/verify-success',
      name: 'verify-success',
      component: VerifySuccessPage,
      meta: {
        title: 'Email Verified',
      },
    },
    {
      path: '/guest',
      name: 'guest-confirmation',
      component: GuestConfirmationPage,
      meta: {
        title: 'Guest Confirmation',
      },
    },
  ],
})

export default router
