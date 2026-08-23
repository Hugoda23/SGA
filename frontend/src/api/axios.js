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
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('sga_token')
      localStorage.removeItem('sga_user')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api
