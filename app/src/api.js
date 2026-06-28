const BASE = '/api'

export async function getCount() {
  const res = await fetch(`${BASE}/count.php`)
  if (!res.ok) throw new Error('Could not load the counter.')
  return res.json()
}

export async function submitAct(formData) {
  const res = await fetch(`${BASE}/submit.php`, { method: 'POST', body: formData })
  const data = await res.json().catch(() => ({ ok: false, message: 'Unexpected server response.' }))
  return { status: res.status, data }
}
