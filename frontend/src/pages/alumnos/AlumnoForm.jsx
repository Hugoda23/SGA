import { useState, useEffect } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import api from '../../api/axios'
import FormInput from '../../components/FormInput'
import { btn } from '../../lib/twClasses'
import Modal from '../../components/Modal'

export default function AlumnoForm() {
  const { id } = useParams()
  const isEdit = !!id
  const navigate = useNavigate()
  const [form, setForm] = useState({
    codigo_mineduc: '', nombre: '', apellido: '', correo: '',
    telefono: '', fecha_nacimiento: '', estado_academico: 'activo',
  })
  const [passwordTemporal, setPasswordTemporal] = useState('')
  const [loading, setLoading] = useState(false)
  const [alertMessage, setAlertMessage] = useState(null)

  const estadosAcademicos = [
    { value: 'activo', label: 'Activo' },
    { value: 'inactivo', label: 'Inactivo' },
    { value: 'egresado', label: 'Egresado' },
    { value: 'retirado', label: 'Retirado' },
  ]

  useEffect(() => {
    if (isEdit) {
      api.get(`/v1/alumnos/${id}`).then((res) => {
        const a = res.data
        setForm({
          codigo_mineduc: a.codigo_mineduc || '',
          nombre: a.nombre || '',
          apellido: a.apellido || '',
          correo: a.correo || '',
          telefono: a.telefono || '',
          fecha_nacimiento: a.fecha_nacimiento || '',
          estado_academico: a.estado_academico || 'activo',
        })
      })
    }
  }, [id, isEdit])

  const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value })

  const handleSubmit = async (e) => {
    e.preventDefault()
    setLoading(true)
    const payload = Object.fromEntries(
      Object.entries(form).map(([k, v]) => [k, v === '' ? null : v])
    )
    try {
      if (isEdit) {
        await api.put(`/v1/alumnos/${id}`, payload)
      } else {
        const res = await api.post('/v1/alumnos', payload)
        setPasswordTemporal(res.data.password_temporal)
        return
      }
      navigate('/alumnos')
    } catch (err) {
      if (err.response?.data?.errors) {
        const msgs = Object.values(err.response.data.errors).flat().join('\n')
        setAlertMessage(msgs)
      } else {
        setAlertMessage(err.response?.data?.message || 'Error al guardar')
      }
    } finally {
      setLoading(false)
    }
  }

  if (passwordTemporal) {
    return (
      <div className="max-w-md mx-auto mt-10 rounded-xl bg-white p-6 shadow-4 space-y-4 dark:bg-surface-dark text-center">
        <h2 className="text-xl font-bold text-success mb-4">¡Alumno creado exitosamente!</h2>
        <p className="text-neutral-600 dark:text-neutral-300 mb-2">Contraseña temporal del alumno:</p>
        <div className="bg-neutral-100 rounded-lg p-3 text-2xl font-mono font-bold text-neutral-800 mb-4">
          {passwordTemporal}
        </div>
        <p className="text-sm text-neutral-500 mb-4">Entregue esta contraseña al alumno. Deberá cambiarla en su primer inicio de sesión.</p>
        <button onClick={() => navigate('/alumnos')} className={btn.primary}>
          Volver a lista
        </button>
      </div>
    )
  }

  return (
    <div className="max-w-2xl mx-auto">
      <h1 className="mb-6 text-3xl font-bold text-neutral-800 dark:text-neutral-100">{isEdit ? 'Editar' : 'Nuevo'} Alumno</h1>
      <form onSubmit={handleSubmit} className="rounded-xl bg-white p-6 shadow-4 space-y-4 dark:bg-surface-dark">
        <FormInput label="Código MINEDUC" name="codigo_mineduc" value={form.codigo_mineduc} onChange={handleChange} required={!isEdit} placeholder="Código del alumno" />
        <div className="grid grid-cols-2 gap-4">
          <FormInput label="Nombre" name="nombre" value={form.nombre} onChange={handleChange} required placeholder="Nombre" />
          <FormInput label="Apellido" name="apellido" value={form.apellido} onChange={handleChange} required placeholder="Apellido" />
        </div>
        <FormInput label="Correo" name="correo" type="email" value={form.correo} onChange={handleChange} placeholder="correo@example.com" />
        <div className="grid grid-cols-2 gap-4">
          <FormInput label="Teléfono" name="telefono" value={form.telefono} onChange={handleChange} placeholder="Teléfono" />
          <FormInput label="Fecha de Nacimiento" name="fecha_nacimiento" type="date" value={form.fecha_nacimiento} onChange={handleChange} required={!isEdit} />
        </div>
        <FormInput label="Estado Académico" name="estado_academico" type="select" value={form.estado_academico} onChange={handleChange} options={estadosAcademicos} />
        <div className="flex gap-3 pt-2">
          <button type="submit" disabled={loading} className={`${btn.primary} disabled:cursor-not-allowed disabled:opacity-60`}>
            {loading ? 'Guardando...' : (isEdit ? 'Actualizar' : 'Crear Alumno')}
          </button>
          <button type="button" onClick={() => navigate('/alumnos')} className={btn.neutral}>
            Cancelar
          </button>
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
