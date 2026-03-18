import { createRouter, createWebHistory } from 'vue-router'
import HomePage from '../views/HomePage.vue'
import CustomerConfirmation from '../components/CustomerConfirmation.vue'
import AnalyticsPage from '../views/AnalyticsPage.vue'
import LoginPage from '../views/LoginPage.vue'
import RegisterPage from '../views/RegisterPage.vue'

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
      path: '/confirm',
      name: 'customer-confirmation',
      component: CustomerConfirmation,
      meta: {
        title: 'Confirm Reservation - Luxe Reserve',
      },
    },
    {
      path: '/analytics',
      name: 'analytics',
      component: AnalyticsPage,
      meta: {
        title: 'Analytics - Luxe Reserve',
      },
    },
    {
      path: '/login',
      name: 'login',
      component: LoginPage,
      meta: {
        title: 'Login - Luxe Reserve',
        guest: true,
      },
    },
    {
      path: '/register',
      name: 'register',
      component: RegisterPage,
      meta: {
        title: 'Register - Luxe Reserve',
        guest: true,
      },
    },
  ],
})

export default router
