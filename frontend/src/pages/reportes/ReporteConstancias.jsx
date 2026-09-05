import { useState, useEffect } from 'react'
import api, { normList } from '../../api/axios'
import { btn, input, table as tbl } from '../../lib/twClasses'
import Modal from '../../components/Modal'
import PdfViewerModal from '../../components/PdfViewerModal'
import usePdfViewer from '../../hooks/usePdfViewer'

export default function ReporteConstancias() {
  const [search, setSearch] = useState('')
  const [alumnos, setAlumnos] = useState([])
  const [loading, setLoading] = useState(true)
  const [alertMessage, setAlertMessage] = useState(null)
  const { pdf, abrirPdf, cerrarPdf, cargando } = usePdfViewer()

  useEffect(() => {
    const fetchAlumnos = async () => {
      try {
        const response = await api.get('/v1/alumnos');
        setAlumnos(normList(response.data));
      } catch (error) {
        console.error('Error fetching alumnos', error);
      } finally {
        setLoading(false);
      }
    }
    fetchAlumnos();
  }, []);

  const handleVerConstancia = async (row) => {
    try {
      await abrirPdf(`/v1/reportes/pdf/constancia/${row.id_alumno}`, {
        clave: row.id_alumno,
        nombreArchivo: `constancia_alumno_${row.id_alumno}.pdf`,
        titulo: `Constancia — ${row.nombre} ${row.apellido}`,
      })
    } catch (error) {
      setAlertMessage(error.message)
    }
  }

  const filteredAlumnos = alumnos.filter(a => 
    (a.nombre && a.nombre.toLowerCase().includes(search.toLowerCase())) || 
    (a.apellido && a.apellido.toLowerCase().includes(search.toLowerCase())) ||
    (a.codigo_mineduc && a.codigo_mineduc.toLowerCase().includes(search.toLowerCase())) ||
    (a.id_alumno && a.id_alumno.toString().includes(search))
  );

  return (
    <div className="max-w-7xl mx-auto pb-12">

      {/* Header */}
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-neutral-800 dark:text-neutral-100 mb-2">Reporte de Constancias</h1>
        <p className="text-base font-medium text-neutral-500 dark:text-neutral-400">Genera constancias oficiales para los alumnos inscritos.</p>
      </div>

      {/* Table Container */}
      <div className="overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
        
        {/* Filter Bar */}
        <div className="flex flex-col items-center justify-between gap-4 border-b-2 border-neutral-100 p-4 dark:border-neutral-600">
          <div className="relative w-full sm:w-80">
            <span className="absolute left-4 top-1/2 -translate-y-1/2 text-neutral-400">
              <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </span>
            <input
              type="text"
              placeholder="Buscar alumno por nombre o carnet..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className={`${input.base} pl-12`}
            />
          </div>
        </div>

        {/* Table */}
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className={tbl.head}>
              <tr>
                <th className={tbl.th}>CARNET</th>
                <th className={tbl.th}>ALUMNO</th>
                <th className={`${tbl.th} text-right`}>ACCIONES</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-neutral-100 dark:divide-neutral-700">
              {loading ? (
                <tr>
                  <td colSpan="3" className="px-4 py-12 text-center">
                    <div className="flex flex-col items-center gap-3 text-neutral-500">
                      <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary-100 border-t-primary"></div>
                      <span className="font-semibold">Cargando alumnos...</span>
                    </div>
                  </td>
                </tr>
              ) : filteredAlumnos.length === 0 ? (
                <tr>
                  <td colSpan="3" className="px-4 py-12 text-center text-neutral-500">
                    <div className="flex flex-col items-center justify-center py-8">
                      <svg className="w-12 h-12 text-neutral-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                      <span className="font-semibold text-neutral-600 dark:text-neutral-300">No hay datos disponibles</span>
                    </div>
                    <p className="text-sm text-neutral-400">Realiza una búsqueda diferente.</p>
                  </td>
                </tr>
              ) : (
                filteredAlumnos.map((row) => (
                  <tr key={row.id_alumno} className={tbl.row}>
                    <td className={`${tbl.td} font-medium`}>{row.codigo_mineduc || `MAT-${row.id_alumno}`}</td>
                    <td className={`${tbl.td} font-medium`}>{row.nombre} {row.apellido}</td>
                    <td className="whitespace-nowrap px-4 py-3 text-right">
                      <button 
                        onClick={() => handleVerConstancia(row)}
                        disabled={cargando === row.id_alumno}
                        className="rounded-lg bg-success-50 px-3 py-1.5 text-xs font-bold text-success transition-colors hover:bg-success hover:text-white disabled:cursor-not-allowed disabled:opacity-60 dark:bg-success-100/10"
                        title="Emitir Constancia"
                      >
                        {cargando === row.id_alumno ? 'Generando...' : 'Emitir Constancia'}
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      <PdfViewerModal
        open={!!pdf}
        onClose={cerrarPdf}
        url={pdf?.url}
        nombreArchivo={pdf?.nombreArchivo}
        titulo={pdf?.titulo}
      />

      <Modal
        open={!!alertMessage}
        onClose={() => setAlertMessage(null)}
        title="Sistema"
        size="sm"
        footer={
          <button type="button" onClick={() => setAlertMessage(null)} className={`${btn.primary} w-full`}>
            Aceptar
          </button>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300 mb-2">{alertMessage}</p>
      </Modal>
    </div>
  )
}
