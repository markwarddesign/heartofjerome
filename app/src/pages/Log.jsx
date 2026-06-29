import { useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import Icon from '../components/Icon.jsx'
import Seo from '../components/Seo.jsx'
import { useCount } from '../App.jsx'
import { submitAct } from '../api.js'

const fmt = (n) => (n == null ? '—' : n.toLocaleString('en-US'))

const EMPTY = { num_acts: '1', name: '', email: '', description: '', logged_idaho: false }

export default function Log() {
  const { count, setCount, refresh } = useCount()
  const [form, setForm] = useState(EMPTY)
  const [file, setFile] = useState(null)
  const [errors, setErrors] = useState({})
  const [submitting, setSubmitting] = useState(false)
  const [success, setSuccess] = useState(null)
  const fileInput = useRef(null)

  const set = (k) => (e) => {
    const value = e.target.type === 'checkbox' ? e.target.checked : e.target.value
    setForm((f) => ({ ...f, [k]: value }))
  }

  function validate() {
    const errs = {}
    const n = Number(form.num_acts)
    if (!Number.isInteger(n) || n < 1 || n > 1000) errs.num_acts = 'Enter a whole number between 1 and 1000.'
    if (!form.email.trim()) errs.email = 'Email address is required.'
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) errs.email = 'Please enter a valid email address.'
    if (form.name.length > 100) errs.name = 'Name is too long.'
    if (form.description.length > 2000) errs.description = 'Please keep the description under 2000 characters.'
    if (file && file.size > 10 * 1024 * 1024) errs.photo = 'File is larger than 10MB.'
    return errs
  }

  async function onSubmit(e) {
    e.preventDefault()
    const errs = validate()
    setErrors(errs)
    if (Object.keys(errs).length) return

    setSubmitting(true)
    const fd = new FormData()
    fd.append('num_acts', form.num_acts)
    fd.append('name', form.name)
    fd.append('email', form.email)
    fd.append('description', form.description)
    fd.append('logged_idaho', form.logged_idaho ? '1' : '')
    if (file) fd.append('photo', file)

    try {
      const { status, data } = await submitAct(fd)
      if (status === 200 && data.ok) {
        if (data.total != null) setCount((c) => ({ ...(c || {}), total: data.total }))
        refresh()
        setSuccess({ ...form, total: data.total })
        window.scrollTo({ top: 0 })
      } else if (status === 422 && data.errors) {
        setErrors(data.errors)
      } else {
        setErrors({ _general: data.message || 'Something went wrong. Please try again.' })
      }
    } catch {
      setErrors({ _general: 'Could not reach the server. Please check your connection and try again.' })
    } finally {
      setSubmitting(false)
    }
  }

  function reset() {
    setForm(EMPTY)
    setFile(null)
    setErrors({})
    setSuccess(null)
    if (fileInput.current) fileInput.current.value = ''
  }

  const goal = count?.goal ?? 2500

  if (success) {
    return (
      <section className="section">
        <div className="wrap">
          <div className="success">
            <div className="success__badge"><Icon name="heart" /></div>
            <h1>Thank you for your kindness!</h1>
            <p className="lede" style={{ margin: 'var(--space-md) auto 0', maxWidth: '46ch' }}>
              Your contribution has been etched into Jerome's living history.
            </p>

            <dl className="success__summary">
              <dt>Acts recorded</dt>
              <dd>{fmt(Number(success.num_acts))}</dd>
              {success.name && (<><dt>Logged by</dt><dd>{success.name}</dd></>)}
              {success.description && (<><dt>Your story</dt><dd>{success.description}</dd></>)}
              <dt>Community total</dt>
              <dd>
                <strong style={{ color: 'var(--color-primary)', fontSize: 'var(--text-2xl)', fontFamily: 'var(--font-display)' }}>
                  {fmt(success.total)}
                </strong>{' '}of {fmt(goal)} toward July 4, 2026
              </dd>
            </dl>

            {success.logged_idaho ? (
              <p className="mt-lg" style={{ color: 'var(--color-success)', fontWeight: 600 }}>
                <Icon name="check" /> Thank you for also logging it at IdahoKindness.com!
              </p>
            ) : (
              <div className="reminder" style={{ textAlign: 'left', marginTop: 'var(--space-xl)' }}>
                <Icon name="info" />
                <p>
                  <strong>One more step:</strong> please also log this act at{' '}
                  <a className="textlink" href="https://www.idahokindness.com/" target="_blank" rel="noopener">IdahoKindness.com</a>{' '}
                  so Jerome is counted in the statewide America250 effort. We've emailed you a reminder too.
                </p>
              </div>
            )}

            <div className="success__actions">
              <button className="btn" onClick={reset}>Log Another Act</button>
              <Link to="/" className="btn btn--ghost">Back to Home</Link>
            </div>
          </div>
        </div>
      </section>
    )
  }

  return (
    <section className="section">
      <Seo
        title="Log an Act of Kindness | The Heart of Jerome"
        description="Logged a kind act in Jerome or the Magic Valley? Record it here in under a minute and watch our community kindness counter grow toward July 4, 2026."
        path="/log"
      />
      <div className="wrap">
        <div className="form-layout">
          <aside className="form-aside">
            <span className="eyebrow"><Icon name="heart" /> Every act counts</span>
            <h1 style={{ fontSize: 'clamp(2.25rem,5vw,var(--text-5xl))', marginTop: 'var(--space-md)' }}>
              Log Your Act of Kindness
            </h1>
            <p className="lede mt-lg">
              Share your story to help us reach our milestone for America's 250th birthday. Every
              single act is a thread in the fabric of Jerome.
            </p>
            <div className="steps steps--stack">
              <div className="step"><span className="step__n">1</span><p>Identify a moment of connection or service.</p></div>
              <div className="step"><span className="step__n">2</span><p>Fill out the simple form.</p></div>
              <div className="step"><span className="step__n">3</span><p>Watch our community counter grow!</p></div>
            </div>
          </aside>

          <div className="form-card">
            {errors._general && <div className="alert alert--error">{errors._general}</div>}

            <div className="reminder">
              <Icon name="info" />
              <p>
                <strong>Don't forget:</strong> please also log this act at{' '}
                <a className="textlink" href="https://www.idahokindness.com/" target="_blank" rel="noopener">IdahoKindness.com</a>{' '}
                so Jerome is counted in the statewide America250 effort. We'll remind you again after you submit.
              </p>
            </div>

            <form onSubmit={onSubmit} noValidate>
              {/* honeypot */}
              <input type="text" name="website" tabIndex={-1} autoComplete="off"
                     style={{ position: 'absolute', left: '-9999px' }} aria-hidden="true"
                     value={form.website || ''} onChange={set('website')} />

              <div className="grid-2">
                <div className="field">
                  <label htmlFor="num_acts">Number of Acts</label>
                  <input id="num_acts" type="number" min="1" max="1000" value={form.num_acts}
                         onChange={set('num_acts')} aria-invalid={!!errors.num_acts} />
                  {errors.num_acts && <p className="err">{errors.num_acts}</p>}
                </div>
                <div className="field">
                  <label htmlFor="name">Your Name <span className="opt">(optional)</span></label>
                  <input id="name" type="text" maxLength={100} placeholder="Jane Doe"
                         value={form.name} onChange={set('name')} aria-invalid={!!errors.name} />
                  {errors.name && <p className="err">{errors.name}</p>}
                </div>
              </div>

              <div className="field">
                <label htmlFor="email">Email Address <span className="req">*</span></label>
                <input id="email" type="email" required placeholder="you@example.com"
                       value={form.email} onChange={set('email')} aria-invalid={!!errors.email} />
                {errors.email && <p className="err">{errors.email}</p>}
              </div>

              <div className="field">
                <label htmlFor="description">Description of Kindness</label>
                <textarea id="description" rows={4} maxLength={2000} placeholder="Tell us what happened…"
                          value={form.description} onChange={set('description')} aria-invalid={!!errors.description} />
                {errors.description && <p className="err">{errors.description}</p>}
              </div>

              <div className="field">
                <label htmlFor="photo">Photo or Video <span className="opt">(optional)</span></label>
                <label className="upload" htmlFor="photo">
                  <Icon name="upload" />
                  <div><strong style={{ color: 'var(--color-primary)' }}>Click to upload</strong> or drag and drop</div>
                  <span className="hint">PNG, JPG, GIF, MP4, MOV — up to 10MB</span>
                  {file && <span className="upload__name">{file.name}</span>}
                  <input id="photo" ref={fileInput} type="file" accept="image/*,video/*"
                         onChange={(e) => setFile(e.target.files?.[0] || null)} style={{ position: 'absolute', width: 1, height: 1, opacity: 0 }} />
                </label>
                {errors.photo && <p className="err">{errors.photo}</p>}
              </div>

              <div className="check">
                <input id="logged_idaho" type="checkbox" checked={form.logged_idaho} onChange={set('logged_idaho')} />
                <label htmlFor="logged_idaho">
                  I have also logged this act at{' '}
                  <a className="textlink" href="https://www.idahokindness.com/" target="_blank" rel="noopener">IdahoKindness.com</a>{' '}
                  to ensure Jerome is represented in the statewide effort.
                </label>
              </div>

              <button type="submit" className="btn btn--block" disabled={submitting}>
                {submitting ? 'Recording your act…' : 'Submit My Act of Kindness'}
              </button>
            </form>
          </div>
        </div>
      </div>
    </section>
  )
}
