/* Shared Tailwind Elements class presets used across the SGA frontend */

const ripple = 'data-twe-ripple-init'

export const btn = {
  primary: `${ripple} inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-6 pb-2 pt-2.5 text-xs font-medium uppercase leading-normal text-white shadow-primary-3 transition duration-150 ease-in-out hover:bg-primary-accent-300 hover:shadow-primary-2 focus:bg-primary-accent-300 focus:shadow-primary-2 focus:outline-none focus:ring-0 active:bg-primary-600 active:shadow-primary-2 dark:shadow-black/30 dark:hover:shadow-dark-strong dark:focus:shadow-dark-strong`,
  secondary: `${ripple} inline-flex items-center justify-center gap-2 rounded-lg bg-secondary px-6 pb-2 pt-2.5 text-xs font-medium uppercase leading-normal text-white shadow-secondary-3 transition duration-150 ease-in-out hover:bg-secondary-accent-300 hover:shadow-secondary-2 focus:bg-secondary-accent-300 focus:shadow-secondary-2 focus:outline-none focus:ring-0 active:bg-secondary-600 active:shadow-secondary-2 dark:shadow-black/30`,
  success: `${ripple} inline-flex items-center justify-center gap-2 rounded-lg bg-success px-6 pb-2 pt-2.5 text-xs font-medium uppercase leading-normal text-white shadow-success-3 transition duration-150 ease-in-out hover:bg-success-accent-300 hover:shadow-success-2 focus:bg-success-accent-300 focus:shadow-success-2 focus:outline-none focus:ring-0 active:bg-success-600 active:shadow-success-2`,
  danger: `${ripple} inline-flex items-center justify-center gap-2 rounded-lg bg-danger px-6 pb-2 pt-2.5 text-xs font-medium uppercase leading-normal text-white shadow-danger-3 transition duration-150 ease-in-out hover:bg-danger-accent-300 hover:shadow-danger-2 focus:bg-danger-accent-300 focus:shadow-danger-2 focus:outline-none focus:ring-0 active:bg-danger-600 active:shadow-danger-2`,
  outline: `${ripple} inline-flex items-center justify-center gap-2 rounded-lg border border-primary px-6 pb-2 pt-2.5 text-xs font-medium uppercase leading-normal text-primary shadow-primary-3 transition duration-150 ease-in-out hover:bg-primary hover:text-white hover:shadow-primary-2 focus:bg-primary focus:text-white focus:shadow-primary-2 focus:outline-none focus:ring-0 active:bg-primary-600 active:shadow-primary-2`,
  outlineDanger: `${ripple} inline-flex items-center justify-center gap-2 rounded-lg border border-danger px-6 pb-2 pt-2.5 text-xs font-medium uppercase leading-normal text-danger shadow-danger-3 transition duration-150 ease-in-out hover:bg-danger hover:text-white hover:shadow-danger-2 focus:bg-danger focus:text-white focus:shadow-danger-2 focus:outline-none focus:ring-0 active:bg-danger-600 active:shadow-danger-2`,
  ghost: `${ripple} inline-flex items-center justify-center gap-2 rounded-lg px-6 pb-2 pt-2.5 text-xs font-medium uppercase leading-normal text-primary transition duration-150 ease-in-out hover:bg-primary-100 hover:text-primary-700 focus:bg-primary-100 focus:text-primary-700 focus:outline-none focus:ring-0 active:bg-primary-200`,
  neutral: `${ripple} inline-flex items-center justify-center gap-2 rounded-lg px-6 pb-2 pt-2.5 text-xs font-medium uppercase leading-normal text-neutral-600 transition duration-150 ease-in-out hover:bg-neutral-100 hover:text-neutral-800 focus:bg-neutral-100 focus:outline-none focus:ring-0 active:bg-neutral-200 dark:text-neutral-300 dark:hover:bg-neutral-700 dark:hover:text-neutral-100`,
}

export const input = {
  base: 'w-full rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-sm font-normal text-neutral-700 shadow-2 transition duration-150 ease-in-out placeholder:text-neutral-400 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-200 dark:placeholder:text-neutral-400',
  label: 'mb-1.5 block text-sm font-medium text-neutral-700 dark:text-neutral-300',
  error: 'mt-1.5 flex items-center gap-1 text-xs font-semibold text-danger',
}

export const card = 'rounded-xl bg-white p-6 shadow-4 dark:bg-surface-dark'

export const table = {
  wrapper: 'overflow-x-auto rounded-t-xl',
  th: 'px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-neutral-500 dark:text-neutral-400',
  td: 'px-4 py-3 text-sm text-neutral-700 dark:text-neutral-200',
  row: 'border-b border-neutral-100 transition-colors hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-700/60',
  head: 'border-b border-neutral-200 bg-neutral-50 dark:border-neutral-600 dark:bg-neutral-700/50',
}

export const badge = {
  success: 'inline-block rounded-full bg-success-50 px-3 py-1 text-xs font-semibold text-success dark:bg-success-900/30 dark:text-success-300',
  danger: 'inline-block rounded-full bg-danger-50 px-3 py-1 text-xs font-semibold text-danger dark:bg-danger-900/30 dark:text-danger-300',
  warning: 'inline-block rounded-full bg-warning-50 px-3 py-1 text-xs font-semibold text-warning dark:bg-warning-900/30 dark:text-warning-300',
  info: 'inline-block rounded-full bg-info-50 px-3 py-1 text-xs font-semibold text-info dark:bg-info-900/30 dark:text-info-300',
  primary: 'inline-block rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary dark:bg-primary-900/30 dark:text-primary-300',
  neutral: 'inline-block rounded-full bg-neutral-100 px-3 py-1 text-xs font-semibold text-neutral-600 dark:bg-neutral-700 dark:text-neutral-300',
}
