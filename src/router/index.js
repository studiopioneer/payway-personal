import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  { path: '/login', name: 'login', component: () => import('@/views/LoginView.vue'), meta: { layout: 'blank' } },
  { path: '/account', name: 'withdrawal', component: () => import('@/views/WithdrawalView.vue'), meta: { requiresAuth: true } },
  { path: '/create-withdrawal', name: 'create-withdrawal', component: () => import('@/views/CreateWithdrawalView.vue'), meta: { requiresAuth: true } },
  { path: '/create-unlock', name: 'create-unlock', component: () => import('@/views/CreateUnlockView.vue'), meta: { requiresAuth: true } },
  { path: '/projects', name: 'projects', component: () => import('@/views/ProjectsView.vue'), meta: { requiresAuth: true } },
  { path: '/create-project', name: 'create-project', component: () => import('@/views/CreateProjectView.vue'), meta: { requiresAuth: true } },
  { path: '/unlock', name: 'unlock', component: () => import('@/views/UnlockView.vue'), meta: { requiresAuth: true } },
  { path: '/referrals', name: 'referrals', component: () => import('@/views/ReferralView.vue'), meta: { requiresAuth: true } },
  { path: '/stats', name: 'stats', component: () => import('@/views/StatsView.vue'), meta: { requiresAuth: true } },
  { path: '/profile', name: 'profile', component: () => import('@/views/ProfileView.vue'), meta: { requiresAuth: true } },
  { path: '/audit', name: 'audit', component: () => import('@/views/AuditView.vue'), meta: { requiresAuth: true } },
  { path: '/audit-history', name: 'audit-history', component: () => import('@/views/AuditHistoryView.vue'), meta: { requiresAuth: true } },
  { path: '/', redirect: '/account' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() { return { top: 0 } },
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('jwtToken')
  if (to.meta.requiresAuth && !token) { next('/login') }
  else if (to.path === '/login' && token) { next('/account') }
  else { next() }
})

export default router
