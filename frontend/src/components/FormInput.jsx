import { input } from '../lib/twClasses'

export default function FormInput({ label, name, type = 'text', value, onChange, required, placeholder, options, error, rows }) {
  const baseClasses = `${input.base} ${error ? 'border-danger focus:border-danger focus:ring-danger' : ''}`
  const labelClasses = input.label

  const ErrorMsg = error && (
    <p className={input.error}>
      <svg className="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      {error}
    </p>
  )

  if (type === 'select') {
    return (
      <div>
        <label className={labelClasses}>{label}{required && <span className="ml-1 text-danger">*</span>}</label>
        <select
          name={name}
          value={value ?? ''}
          onChange={onChange}
          required={required}
          className={baseClasses}
        >
          <option value="">Seleccionar...</option>
          {options?.map((opt) => (
            <option key={opt.value} value={opt.value}>{opt.label}</option>
          ))}
        </select>
        {ErrorMsg}
      </div>
    )
  }

  if (type === 'textarea') {
    return (
      <div>
        <label className={labelClasses}>{label}{required && <span className="ml-1 text-danger">*</span>}</label>
        <textarea
          name={name}
          value={value ?? ''}
          onChange={onChange}
          required={required}
          placeholder={placeholder}
          rows={rows || 3}
          className={`${baseClasses} resize-none`}
        />
        {ErrorMsg}
      </div>
    )
  }

  return (
    <div>
      <label className={labelClasses}>{label}{required && <span className="ml-1 text-danger">*</span>}</label>
      <input
        type={type}
        name={name}
        value={value ?? ''}
        onChange={onChange}
        required={required}
        placeholder={placeholder}
        className={baseClasses}
      />
      {ErrorMsg}
    </div>
  )
}
