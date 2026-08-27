import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: { 'Content-Type': 'application/json' },
})

export const normList = (data) => (Array.isArray(data) ? data : data?.data ?? [])

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('sga_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

api.interceptors.response.use(
  (response) => {
    // El backend renueva el token a mitad de su vida útil (ver
    // RefreshSanctumToken) para que la sesión nunca expire mientras el
    // usuario esté activo; si viene uno nuevo, lo adoptamos en silencio.
    const newToken = response.headers['x-new-token']
    if (newToken) {
      localStorage.setItem('sga_token', newToken)
    }
    return response
  },
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('sga_token')
      localStorage.removeItem('sga_user')
      window.location.href = '/login'
    }
    if (error.response?.status === 503 && error.response?.data?.mantenimiento && window.location.pathname !== '/mantenimiento') {
      sessionStorage.setItem('sga_mantenimiento_mensaje', error.response.data.message || '')
      window.location.href = '/mantenimiento'
    }
    return Promise.reject(error)
  }
)

export default api
