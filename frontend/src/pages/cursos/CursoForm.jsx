import { useState, useEffect } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import api from '../../api/axios'
import FormInput from '../../components/FormInput'
import { btn, input } from '../../lib/twClasses'
import Modal from '../../components/Modal'

export default function CursoForm() {
  const { id } = useParams()
  const isEdit = !!id
  const navigate = useNavigate()
  const [form, setForm] = useState({ nombre_curso: '', descripcion: '', carreras: [] })
  const [carreras, setCarreras] = useState([])
  const [alertMessage, setAlertMessage] = useState(null)

  useEffect(() => {
    api.get('/v1/carreras').then((res) => setCarreras(res.data.map((c) => ({ value: c.id_carrera, label: c.nombre_carrera }))))
    if (isEdit) api.get(`/v1/cursos/${id}`).then((res) => {
      const c = res.data; setForm({ nombre_curso: c.nombre_curso || '', descripcion: c.descripcion || '', carreras: (c.carreras || []).map((x) => x.id_carrera) })
    })
  }, [id, isEdit])

  const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value })

  const toggleCarrera = (idCarrera) => {
    setForm((prev) => ({
      ...prev,
      carreras: prev.carreras.includes(idCarrera)
        ? prev.carreras.filter((c) => c !== idCarrera)
        : [...prev.carreras, idCarrera],
    }))
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    const payload = {
      nombre_curso: form.nombre_curso,
      descripcion: form.descripcion === '' ? null : form.descripcion,
      carreras: form.carreras,
    }
    try {
      if (isEdit) await api.put(`/v1/cursos/${id}`, payload)
      else await api.post('/v1/cursos', payload)
      navigate('/cursos')
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
      <h1 className="mb-6 text-3xl font-bold text-neutral-800 dark:text-neutral-100">{isEdit ? 'Editar' : 'Nuevo'} Curso</h1>
      <form onSubmit={handleSubmit} className="rounded-xl bg-white p-6 shadow-4 space-y-4 dark:bg-surface-dark">
        <FormInput label="Nombre del Curso" name="nombre_curso" value={form.nombre_curso} onChange={handleChange} required placeholder="Nombre" />
        <FormInput label="Descripción" name="descripcion" type="textarea" value={form.descripcion} onChange={handleChange} placeholder="Descripción" />
        <div>
          <label className={input.label}>Carreras (un curso puede pertenecer a varias)</label>
          <div className="grid max-h-60 grid-cols-1 gap-2 overflow-y-auto rounded-lg border border-neutral-300 bg-white p-3 dark:border-neutral-600 dark:bg-neutral-800">
            {carreras.length === 0 ? (
              <p className="text-sm text-neutral-500 dark:text-neutral-400">No hay carreras registradas.</p>
            ) : carreras.map((c) => (
              <label key={c.value} className="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-1.5 text-sm text-neutral-700 transition-colors hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-700">
                <input
                  type="checkbox"
                  checked={form.carreras.includes(c.value)}
                  onChange={() => toggleCarrera(c.value)}
                  className="h-4 w-4 rounded border-neutral-300 text-primary focus:ring-primary"
                />
                <span className="font-medium">{c.label}</span>
              </label>
            ))}
          </div>
        </div>
        <div className="flex gap-3 pt-2">
          <button type="submit" className={btn.primary}>{isEdit ? 'Actualizar' : 'Crear'}</button>
          <button type="button" onClick={() => navigate('/cursos')} className={btn.neutral}>Cancelar</button>
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
