import api from '../api/axios'

const VAPID_PUBLIC_KEY = import.meta.env.VITE_VAPID_PUBLIC_KEY

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
  const rawData = atob(base64)
  return Uint8Array.from([...rawData].map((c) => c.charCodeAt(0)))
}

export async function registerServiceWorker() {
  if (!('serviceWorker' in navigator)) return null
  try {
    return await navigator.serviceWorker.register('/sw.js')
  } catch {
    return null
  }
}

/**
 * Pide permiso de notificaciones (si hace falta) y suscribe al usuario
 * a Web Push. Si el permiso ya fue concedido antes, no vuelve a preguntar.
 */
export async function subscribeToPush() {
  if (!('serviceWorker' in navigator) || !('PushManager' in window) || !VAPID_PUBLIC_KEY) return

  const permission = await Notification.requestPermission()
  if (permission !== 'granted') return

  const registration = await navigator.serviceWorker.ready
  let subscription = await registration.pushManager.getSubscription()

  if (!subscription) {
    subscription = await registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
    })
  }

  await api.post('/v1/push/subscribe', subscription.toJSON())
}

export async function unsubscribeFromPush() {
  if (!('serviceWorker' in navigator)) return

  const registration = await navigator.serviceWorker.getRegistration()
  const subscription = await registration?.pushManager.getSubscription()

  if (subscription) {
    await api.post('/v1/push/unsubscribe', { endpoint: subscription.endpoint }).catch(() => {})
    await subscription.unsubscribe()
  }
}
