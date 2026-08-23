import { useState, useEffect } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import api from '../../api/axios'
import FormInput from '../../components/FormInput'
import { btn, input } from '../../lib/twClasses'
import Modal from '../../components/Modal'

export default function PensumForm() {
  const { id } = useParams(); const isEdit = !!id; const navigate = useNavigate()
  const [form, setForm] = useState({ id_carrera: '', id_grado: '', cursos: {} })
  const [carreras, setCarreras] = useState([]); const [cursos, setCursos] = useState([]); const [grados, setGrados] = useState([])
  const [alertMessage, setAlertMessage] = useState(null)
  const [saving, setSaving] = useState(false)

  useEffect(() => {
    api.get('/v1/carreras').then((r) => setCarreras(r.data.map((c) => ({ value: c.id_carrera, label: c.nombre_carrera }))))
    api.get('/v1/cursos').then((r) => setCursos(r.data.map((c) => ({ value: c.id_curso, label: c.nombre_curso }))))
    api.get('/v1/grados').then((r) => setGrados(r.data.map((g) => ({ value: g.id_grado, label: g.nivel ? `${g.nombre} — ${g.nivel}` : g.nombre }))))
    if (isEdit) api.get(`/v1/pensums/${id}`).then(async (r) => {
      const p = r.data
      const idCarrera = p.id_carrera
      const idGrado = p.id_grado ?? null
      const all = (await api.get('/v1/pensums')).data
      const group = all.filter((x) => x.id_carrera == idCarrera && (x.id_grado ?? null) == idGrado)
      const cursos = {}
      for (const x of group) {
        cursos[x.id_curso] = { checked: true, obligatorio: x.obligatorio ?? true }
      }
      setForm({ id_carrera: idCarrera || '', id_grado: idGrado ?? '', cursos })
    })
  }, [id, isEdit])

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target
    setForm({ ...form, [name]: type === 'checkbox' ? checked : value })
  }

  const toggleCurso = (idCurso) => {
    setForm((prev) => {
      const item = prev.cursos[idCurso] || { checked: false, obligatorio: true }
      return { ...prev, cursos: { ...prev.cursos, [idCurso]: { ...item, checked: !item.checked } } }
    })
  }

  const toggleObligatorio = (idCurso) => {
    setForm((prev) => {
      const item = prev.cursos[idCurso] || { checked: false, obligatorio: true }
      return { ...prev, cursos: { ...prev.cursos, [idCurso]: { ...item, obligatorio: !item.obligatorio } } }
    })
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    const { id_carrera, cursos } = form
    const id_grado = form.id_grado === '' ? null : form.id_grado
    const seleccionados = Object.entries(cursos).filter(([, v]) => v.checked)
    setSaving(true)
    try {
      if (isEdit) {
        await api.put(`/v1/pensums/${id}`, { id_carrera, id_grado })
        const group = (await api.get('/v1/pensums')).data.filter((p) => p.id_carrera == id_carrera && p.id_grado == id_grado)
        const selectedIds = new Set(seleccionados.map(([cid]) => Number(cid)))
        for (const p of group) {
          if (!selectedIds.has(p.id_curso)) await api.delete(`/v1/pensums/${p.id_pensum}`)
        }
        const fresh = (await api.get('/v1/pensums')).data.filter((p) => p.id_carrera == id_carrera && p.id_grado == id_grado)
        const byCurso = new Map(fresh.map((p) => [p.id_curso, p]))
        for (const [cid, val] of seleccionados) {
          const id_curso = Number(cid)
          const existing = byCurso.get(id_curso)
          if (existing) {
            if (existing.obligatorio !== val.obligatorio) {
              await api.put(`/v1/pensums/${existing.id_pensum}`, { id_carrera, id_grado, obligatorio: val.obligatorio })
            }
          } else {
            await api.post('/v1/pensums', { id_carrera, id_curso, id_grado, obligatorio: val.obligatorio })
          }
        }
      } else {
        for (const [cid, val] of seleccionados) {
          await api.post('/v1/pensums', { id_carrera, id_curso: Number(cid), id_grado, obligatorio: val.obligatorio })
        }
      }
      navigate('/pensum')
    } catch (err) {
      if (err.response?.data?.errors) {
        const msgs = Object.values(err.response.data.errors).flat().join('\n')
        setAlertMessage(msgs)
      } else {
        setAlertMessage(err.response?.data?.message || 'Error al guardar')
      }
    } finally {
      setSaving(false)
    }
  }

  return (
    <div className="max-w-2xl mx-auto">
      <h1 className="mb-6 text-3xl font-bold text-neutral-800 dark:text-neutral-100">{isEdit ? 'Editar' : 'Nuevo'} Pensum</h1>
      <form onSubmit={handleSubmit} className="rounded-xl bg-white p-6 shadow-4 space-y-4 dark:bg-surface-dark">
        <FormInput label="Carrera" name="id_carrera" type="select" value={form.id_carrera} onChange={handleChange} required options={carreras} />
        <FormInput label="Grado" name="id_grado" type="select" value={form.id_grado} onChange={handleChange} options={grados} />
        <div>
          <label className={input.label}>Cursos (una carrera y grado pueden tener varios cursos, marca cuáles son obligatorios)</label>
          <div className="grid max-h-60 grid-cols-1 gap-1 overflow-y-auto rounded-lg border border-neutral-300 bg-white p-3 dark:border-neutral-600 dark:bg-neutral-800">
            {cursos.length === 0 ? (
              <p className="text-sm text-neutral-500 dark:text-neutral-400">No hay cursos registrados.</p>
            ) : cursos.map((c) => {
              const item = form.cursos[c.value] || { checked: false, obligatorio: true }
              return (
                <div key={c.value} className="flex items-center justify-between gap-3 rounded-lg px-2 py-1.5 text-sm text-neutral-700 transition-colors hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-700">
                  <label className="flex flex-1 cursor-pointer items-center gap-3">
                    <input
                      type="checkbox"
                      checked={item.checked}
                      onChange={() => toggleCurso(c.value)}
                      className="h-4 w-4 rounded border-neutral-300 text-primary focus:ring-primary"
                    />
                    <span className="font-medium">{c.label}</span>
                  </label>
                  <label className="flex cursor-pointer items-center gap-1.5 text-xs">
                    <input
                      type="checkbox"
                      checked={item.obligatorio}
                      onChange={() => toggleObligatorio(c.value)}
                      className="rounded border-neutral-300 text-primary focus:ring-primary"
                    />
                    <span className="text-neutral-700 font-medium dark:text-neutral-200">Obligatorio</span>
                  </label>
                </div>
              )
            })}
          </div>
        </div>
        <div className="flex gap-3 pt-2">
          <button type="submit" disabled={saving} className={btn.primary}>{saving ? 'Guardando...' : (isEdit ? 'Actualizar' : 'Crear')}</button>
          <button type="button" onClick={() => navigate('/pensum')} className={btn.neutral}>Cancelar</button>
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
