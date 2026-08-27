import { useState, useEffect } from 'react'
import FormInput from '../../components/FormInput'
import api from '../../api/axios'
import { btn, input } from '../../lib/twClasses'
import Modal from '../../components/Modal'

const FORM_VACIO = { nombre_institucion: '', nota_minima: '', version_sistema: '', mantenimiento_activo: false, mantenimiento_mensaje: '' }

export default function ConfiguracionForm() {
  const [form, setForm] = useState(FORM_VACIO)
  const [activoGuardado, setActivoGuardado] = useState(false)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [alertMessage, setAlertMessage] = useState(null)
  const [confirmarActivar, setConfirmarActivar] = useState(false)

  const cargar = () => {
    api.get('/v1/configuraciones').then((r) => {
      setForm({
        nombre_institucion: r.data.nombre_institucion || '',
        nota_minima: r.data.nota_minima ?? '',
        version_sistema: r.data.version_sistema || '',
        mantenimiento_activo: !!r.data.mantenimiento_activo,
        mantenimiento_mensaje: r.data.mantenimiento_mensaje || '',
      })
      setActivoGuardado(!!r.data.mantenimiento_activo)
    }).catch(console.error).finally(() => setLoading(false))
  }

  useEffect(() => { cargar() }, [])

  const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value })

  const guardar = async () => {
    setSaving(true)
    try {
      const r = await api.put('/v1/configuraciones', {
        nombre_institucion: form.nombre_institucion,
        nota_minima: Number(form.nota_minima),
        version_sistema: form.version_sistema,
        mantenimiento_activo: form.mantenimiento_activo,
        mantenimiento_mensaje: form.mantenimiento_mensaje,
      })
      setForm({
        nombre_institucion: r.data.nombre_institucion || '',
        nota_minima: r.data.nota_minima ?? '',
        version_sistema: r.data.version_sistema || '',
        mantenimiento_activo: !!r.data.mantenimiento_activo,
        mantenimiento_mensaje: r.data.mantenimiento_mensaje || '',
      })
      setActivoGuardado(!!r.data.mantenimiento_activo)
      setAlertMessage({ type: 'success', text: 'Configuración actualizada correctamente.' })
    } catch (err) {
      setAlertMessage({ type: 'error', text: err.response?.data?.message || 'Error al guardar' })
    } finally {
      setSaving(false)
    }
  }

  const handleSubmit = (e) => {
    e.preventDefault()
    // Activar mantenimiento deja afuera a todo el mundo menos admin —
    // confirmamos antes de aplicarlo, no al desactivarlo ni al resto de
    // los cambios.
    if (form.mantenimiento_activo && !activoGuardado) {
      setConfirmarActivar(true)
      return
    }
    guardar()
  }

  return (
    <div className="max-w-2xl mx-auto">
      <h1 className="mb-2 text-3xl font-bold text-neutral-800 dark:text-neutral-100">Configuración del Sistema</h1>
      <p className="mb-6 text-sm font-medium text-neutral-500 dark:text-neutral-400">
        Ajustes generales que afectan los reportes, boletines, la promoción de alumnos y la disponibilidad del sistema.
      </p>

      {loading ? (
        <div className="rounded-xl bg-white p-6 text-center text-sm font-medium text-neutral-400 shadow-4 dark:bg-surface-dark">
          Cargando configuración...
        </div>
      ) : (
        <form onSubmit={handleSubmit} className="space-y-6">
          <div className="rounded-xl bg-white p-6 shadow-4 space-y-4 dark:bg-surface-dark">
            <FormInput
              label="Nombre de la Institución"
              name="nombre_institucion"
              value={form.nombre_institucion}
              onChange={handleChange}
              required
              placeholder="ej. Instituto Florencio Carrascoza"
            />
            <p className="-mt-3 text-xs text-neutral-400">Aparece en el encabezado de los PDFs y en la verificación pública de documentos.</p>

            <FormInput
              label="Nota Mínima para Aprobar"
              name="nota_minima"
              type="number"
              min={0}
              max={100}
              value={form.nota_minima}
              onChange={handleChange}
              required
              placeholder="61"
            />
            <p className="-mt-3 text-xs text-neutral-400">Define quién aprueba o reprueba en boletines, kárdex y al cerrar un periodo (promoción de alumnos).</p>

            <FormInput
              label="Versión del Sistema"
              name="version_sistema"
              value={form.version_sistema}
              onChange={handleChange}
              required
              placeholder="ej. 1.0.0"
            />
            <p className="-mt-3 text-xs text-neutral-400">Se muestra en la pantalla de inicio de sesión y en el menú — actualizala manualmente cada vez que despliegues cambios.</p>
          </div>

          <div className="rounded-xl bg-white p-6 shadow-4 space-y-4 dark:bg-surface-dark">
            <div>
              <h2 className="text-lg font-bold text-neutral-800 dark:text-neutral-100">Modo Mantenimiento</h2>
              <p className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                Mientras esté activo, nadie puede iniciar sesión ni usar el sistema excepto la cuenta admin — útil para
                actualizar en producción sin que alguien quede a mitad de una acción.
              </p>
            </div>

            <label className="flex cursor-pointer items-center justify-between gap-4 rounded-lg border border-neutral-200 p-3 dark:border-neutral-600">
              <span>
                <span className="block text-sm font-bold text-neutral-700 dark:text-neutral-200">Activar modo mantenimiento</span>
                <span className="block text-xs text-neutral-400">Bloquea el acceso a todos los roles excepto admin.</span>
              </span>
              <input
                type="checkbox"
                checked={form.mantenimiento_activo}
                onChange={(e) => setForm({ ...form, mantenimiento_activo: e.target.checked })}
                className="h-5 w-5 shrink-0 rounded border-neutral-300 text-danger accent-danger focus:ring-danger dark:border-neutral-500 dark:bg-neutral-800"
              />
            </label>

            {form.mantenimiento_activo && (
              <div className="rounded-lg border border-danger-200 bg-danger-50 p-3 text-xs font-bold text-danger dark:border-danger-900/50 dark:bg-danger-900/10">
                Al guardar, todos los usuarios menos admin quedarán bloqueados de inmediato.
              </div>
            )}

            <div>
              <label className={input.label}>Mensaje para los usuarios</label>
              <textarea
                name="mantenimiento_mensaje"
                value={form.mantenimiento_mensaje}
                onChange={handleChange}
                rows={2}
                placeholder="ej. Estamos actualizando el sistema, volvé a intentarlo en unos minutos."
                className={input.base}
              />
              <p className="mt-1 text-xs text-neutral-400">Es lo que ven los usuarios bloqueados al intentar entrar.</p>
            </div>
          </div>

          <div className="flex gap-3">
            <button type="submit" disabled={saving} className={`${btn.primary} disabled:cursor-not-allowed disabled:opacity-60`}>
              {saving ? 'Guardando...' : 'Guardar Configuración'}
            </button>
          </div>
        </form>
      )}

      <Modal
        open={confirmarActivar}
        onClose={() => setConfirmarActivar(false)}
        title="Confirmar mantenimiento"
        size="sm"
        footer={
          <>
            <button className={btn.ghost} onClick={() => setConfirmarActivar(false)}>Cancelar</button>
            <button className={btn.danger} onClick={() => { setConfirmarActivar(false); guardar() }}>Sí, activar</button>
          </>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300">
          Vas a activar el modo mantenimiento. Nadie va a poder iniciar sesión ni usar el sistema excepto tu cuenta admin,
          hasta que lo desactives desde aquí mismo. ¿Confirmás?
        </p>
      </Modal>

      <Modal
        open={!!alertMessage}
        onClose={() => setAlertMessage(null)}
        title="Sistema"
        size="sm"
        footer={
          <button className={`${btn.primary} w-full`} onClick={() => setAlertMessage(null)}>
            Aceptar
          </button>
        }
      >
        <p className={`text-sm whitespace-pre-line ${alertMessage?.type === 'error' ? 'text-danger' : 'text-neutral-600 dark:text-neutral-300'}`}>
          {alertMessage?.text}
        </p>
      </Modal>
    </div>
  )
}
