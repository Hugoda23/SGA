import { useState, useEffect } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import api, { normList } from '../../api/axios'
import FormInput from '../../components/FormInput'
import { btn } from '../../lib/twClasses'
import Modal from '../../components/Modal'

export default function TareaForm() {
  const { id } = useParams(); const isEdit = !!id; const navigate = useNavigate()
  const [form, setForm] = useState({ titulo: '', descripcion: '', fecha_entrega: '', id_asignacion: '' })
  const [asignaciones, setAsignaciones] = useState([])
  const [alertMessage, setAlertMessage] = useState(null)

  useEffect(() => {
    api.get('/v1/asignaciones').then((r) => setAsignaciones(normList(r.data).map((a) => ({ value: a.id_asignacion, label: `${a.curso?.nombre_curso} - ${a.catedratico?.nombre}` }))))
    if (isEdit) api.get(`/v1/tareas/${id}`).then((r) => { const t = r.data; setForm({ titulo: t.titulo || '', descripcion: t.descripcion || '', fecha_entrega: t.fecha_entrega || '', id_asignacion: t.id_asignacion || '' }) })
  }, [id, isEdit])

  const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value })
  const handleSubmit = async (e) => {
    e.preventDefault()
    const payload = Object.fromEntries(Object.entries(form).map(([k, v]) => [k, v === '' ? null : v]))
    try {
      if (isEdit) await api.put(`/v1/tareas/${id}`, payload)
      else await api.post('/v1/tareas', payload)
      navigate('/tareas')
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
      <h1 className="mb-6 text-3xl font-bold text-neutral-800 dark:text-neutral-100">{isEdit ? 'Editar' : 'Nueva'} Tarea</h1>
      <form onSubmit={handleSubmit} className="rounded-xl bg-white p-6 shadow-4 space-y-4 dark:bg-surface-dark">
        <FormInput label="Título" name="titulo" value={form.titulo} onChange={handleChange} required placeholder="Título de la tarea" />
        <FormInput label="Descripción" name="descripcion" type="textarea" value={form.descripcion} onChange={handleChange} placeholder="Descripción" />
        <FormInput label="Fecha de Entrega" name="fecha_entrega" type="date" value={form.fecha_entrega} onChange={handleChange} />
        <FormInput label="Asignación" name="id_asignacion" type="select" value={form.id_asignacion} onChange={handleChange} required options={asignaciones} />
        <div className="flex gap-3 pt-2">
          <button type="submit" className={btn.primary}>{isEdit ? 'Actualizar' : 'Crear'}</button>
          <button type="button" onClick={() => navigate('/tareas')} className={btn.neutral}>Cancelar</button>
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
