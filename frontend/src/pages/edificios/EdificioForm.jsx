import { useState, useEffect } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import api from '../../api/axios'
import FormInput from '../../components/FormInput'
import { btn } from '../../lib/twClasses'
import Modal from '../../components/Modal'

export default function EdificioForm() {
  const { id } = useParams(); const isEdit = !!id; const navigate = useNavigate()
  const [form, setForm] = useState({ nombre: '', ubicacion: '' })
  const [alertMessage, setAlertMessage] = useState(null)
  useEffect(() => { if (isEdit) api.get(`/v1/edificios/${id}`).then((r) => { const e = r.data; setForm({ nombre: e.nombre || '', ubicacion: e.ubicacion || '' }) }) }, [id, isEdit])
  const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value })
  const handleSubmit = async (e) => {
    e.preventDefault()
    const payload = Object.fromEntries(Object.entries(form).map(([k, v]) => [k, v === '' ? null : v]))
    try {
      if (isEdit) await api.put(`/v1/edificios/${id}`, payload)
      else await api.post('/v1/edificios', payload)
      navigate('/edificios')
    } catch (err) {
      if (err.response?.data?.errors) {
        const msgs = Object.values(err.response.data.errors).flat().join('\n')
        setAlertMessage(msgs)
      } else {
        setAlertMessage(err.response?.data?.message || 'Error al guardar')
      }
    }
  }
  return (
    <div className="max-w-2xl mx-auto">
      <h1 className="mb-6 text-3xl font-bold text-neutral-800 dark:text-neutral-100">{isEdit ? 'Editar' : 'Nuevo'} Edificio</h1>
      <form onSubmit={handleSubmit} className="rounded-xl bg-white p-6 shadow-4 space-y-4 dark:bg-surface-dark">
        <FormInput label="Nombre" name="nombre" value={form.nombre} onChange={handleChange} required placeholder="Nombre del edificio" />
        <FormInput label="Ubicación" name="ubicacion" value={form.ubicacion} onChange={handleChange} placeholder="Ubicación" />
        <div className="flex gap-3 pt-2">
          <button type="submit" className={btn.primary}>{isEdit ? 'Actualizar' : 'Crear'}</button>
          <button type="button" onClick={() => navigate('/edificios')} className={btn.neutral}>Cancelar</button>
        </div>
      </form>

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
        <p className="text-sm text-neutral-600 dark:text-neutral-300 whitespace-pre-line">{alertMessage}</p>
      </Modal>
    </div>
  )
}
