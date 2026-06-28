import { Link } from 'react-router-dom'

export default function Footer() {
  return (
    <footer className="site-footer">
      <div className="wrap">
        <div>
          <Link to="/" className="brand">
            <img src="/logo.svg" alt="The Heart of Jerome" />
          </Link>
          <p className="site-footer__copy">
            &copy; {new Date().getFullYear()} The Heart of Jerome. Honoring our heritage, cultivating our future.
          </p>
        </div>
        <nav className="site-footer__links" aria-label="Footer">
          <Link to="/ideas">Get Ideas</Link>
          <Link to="/log">Log Your Act</Link>
          <a href="https://www.idahokindness.com/" target="_blank" rel="noopener">IdahoKindness.com</a>
        </nav>
      </div>
    </footer>
  )
}
