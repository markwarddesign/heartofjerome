import { useEffect, useRef, useState } from 'react'
import { createPortal } from 'react-dom'
import { NavLink, useLocation, useNavigate } from 'react-router-dom'
import Icon from './Icon.jsx'

const LINKS = [
  { to: '/', label: 'Our Mission', end: true },
  { to: '/ideas', label: 'Get Ideas' },
  { to: 'https://www.idahokindness.com/', label: 'America250', external: true },
  { to: '#connect', label: 'Connect', connect: true },
]

function NavItems({ onNavigate, goConnect }) {
  return LINKS.map((link) => {
    if (link.external) {
      return (
        <a key={link.label} href={link.to} target="_blank" rel="noopener" onClick={onNavigate}>
          {link.label}
        </a>
      )
    }
    if (link.connect) {
      return (
        <a
          key={link.label}
          href="/#connect"
          onClick={(e) => {
            e.preventDefault()
            goConnect()
            onNavigate?.()
          }}
        >
          {link.label}
        </a>
      )
    }
    return (
      <NavLink key={link.label} to={link.to} end={link.end} onClick={onNavigate}>
        {link.label}
      </NavLink>
    )
  })
}

export default function Header() {
  const [open, setOpen] = useState(false)
  const closeBtn = useRef(null)
  const drawerRef = useRef(null)
  const panelRef = useRef(null)
  const backdropRef = useRef(null)
  const animsRef = useRef(null)
  const prevOpen = useRef(false)
  const location = useLocation()
  const navigate = useNavigate()

  // Build the panel + backdrop animations once, paused at the closed state.
  // We drive them imperatively (play/reverse) so the slide ALWAYS runs in both
  // directions — no reliance on CSS transitions, which were silently no-op'ing.
  useEffect(() => {
    const panel = panelRef.current
    const backdrop = backdropRef.current
    if (!panel || !backdrop) return
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches

    const panelAnim = panel.animate(
      [{ transform: 'translateX(100%)' }, { transform: 'translateX(0)' }],
      { duration: reduce ? 0 : 340, easing: 'cubic-bezier(0.16, 1, 0.3, 1)', fill: 'both' }
    )
    const backdropAnim = backdrop.animate(
      [{ opacity: 0 }, { opacity: 1 }],
      { duration: reduce ? 0 : 240, easing: 'linear', fill: 'both' }
    )
    panelAnim.pause()
    backdropAnim.pause()
    animsRef.current = { panelAnim, backdropAnim }

    return () => {
      panelAnim.cancel()
      backdropAnim.cancel()
    }
  }, [])

  // Play forward to open, reverse to close. Guarded so it never fires on mount
  // (incl. StrictMode's double-invoke) — only on a real open/close change.
  useEffect(() => {
    const a = animsRef.current
    if (!a || prevOpen.current === open) return
    prevOpen.current = open
    const rate = open ? 1 : -1
    a.panelAnim.playbackRate = rate
    a.backdropAnim.playbackRate = rate
    a.panelAnim.play()
    a.backdropAnim.play()
  }, [open])

  // Closed drawer stays in the DOM — mark it inert so links can't be tabbed/read.
  useEffect(() => {
    if (drawerRef.current) drawerRef.current.inert = !open
  }, [open])

  const goConnect = () => {
    if (location.pathname === '/') {
      document.getElementById('connect')?.scrollIntoView({ behavior: 'smooth' })
    } else {
      navigate('/', { state: { scrollTo: 'connect' } })
    }
  }

  // Lock scroll + focus the close button while the drawer is open; Escape closes.
  useEffect(() => {
    if (!open) return
    document.body.style.overflow = 'hidden'
    closeBtn.current?.focus()
    const onKey = (e) => e.key === 'Escape' && setOpen(false)
    window.addEventListener('keydown', onKey)
    return () => {
      document.body.style.overflow = ''
      window.removeEventListener('keydown', onKey)
    }
  }, [open])

  // Close the drawer whenever the route changes.
  useEffect(() => setOpen(false), [location.pathname])

  return (
    <header className="site-header">
      <div className="wrap nav">
        <NavLink to="/" className="brand" aria-label="The Heart of Jerome — home">
          <img src="/logo.svg" alt="The Heart of Jerome" width="200" height="58" />
        </NavLink>

        <nav className="nav__links" aria-label="Primary">
          <NavItems goConnect={goConnect} />
        </nav>

        <NavLink to="/log" className="btn nav__cta">Log Your Act</NavLink>

        <button
          className="nav__toggle"
          aria-label="Open menu"
          aria-expanded={open}
          aria-controls="mobile-drawer"
          onClick={() => setOpen(true)}
        >
          <Icon name="menu" />
        </button>
      </div>

      {/* Mobile drawer — portaled to <body> so the sticky header can't trap it */}
      {createPortal(
        <div ref={drawerRef} className={`drawer ${open ? 'is-open' : ''}`} id="mobile-drawer" role="dialog" aria-modal="true" aria-label="Menu">
          <div ref={backdropRef} className="drawer__backdrop" onClick={() => setOpen(false)} />
          <div ref={panelRef} className="drawer__panel">
            <div className="drawer__top">
              <img src="/logo.svg" alt="The Heart of Jerome" className="drawer__logo" />
              <button ref={closeBtn} className="nav__toggle" aria-label="Close menu" onClick={() => setOpen(false)}>
                <Icon name="close" />
              </button>
            </div>
            <nav className="drawer__links" aria-label="Mobile">
              <NavItems onNavigate={() => setOpen(false)} goConnect={goConnect} />
            </nav>
            <NavLink to="/log" className="btn btn--block" onClick={() => setOpen(false)}>
              Log Your Act
            </NavLink>
          </div>
        </div>,
        document.body
      )}
    </header>
  )
}
