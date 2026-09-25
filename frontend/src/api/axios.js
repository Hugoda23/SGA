import axios from 'axios'

// Se inyecta en tiempo de build (build-arg VITE_API_URL). Si el build corre sin
// el .env, queda vacio y el SPA pega a rutas relativas que Nginx resuelve con el
// propio index.html: las respuestas llegan como HTML con 200 y la sesion se
// guarda corrupta. Mejor fallar fuerte y visible que arrastrar ese estado.
const baseURL = import.meta.env.VITE_API_URL
if (!baseURL) {
  throw new Error(
    'VITE_API_URL no esta definida. El frontend se compilo sin sus variables de entorno.'
  )
}

const api = axios.create({
  baseURL,
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
    // Nginx sirve el index.html del SPA ante cualquier ruta que no matchee
    // /api, con 200. Axios no lo considera un error y deja el HTML en
    // response.data; sin este chequeo se propaga como si fuera un objeto.
    if (typeof response.data === 'string' && response.data.startsWith('<')) {
      return Promise.reject(
        new Error(`La API devolvio HTML en lugar de JSON (${response.config?.url}).`)
      )
    }
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
