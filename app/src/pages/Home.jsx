import { useEffect } from 'react'
import { Link, useLocation } from 'react-router-dom'
import Icon from '../components/Icon.jsx'
import { useCount } from '../App.jsx'

const fmt = (n) => (n == null ? '—' : n.toLocaleString('en-US'))

const HERO_IMG =
  'https://lh3.googleusercontent.com/aida-public/AB6AXuBBwvkgGKlmCS9NNWT7Fy59T06LcT6SnOVv3YxobZuaNHFe5UG1skOHH5jhR-_JPo5sglv82wDDXsFes-mzwSn00rVpQwG3OvnZMUzrdqlrEE7y19tnuMs_ZZzWoh_9qSmWdN8_l4YUYdYrcuoGfQZ-dp-Irxx9Jo8PAU5U7-yoKXJLogkH-A8EfBgTrF_tw0bMNuIpFHvWlehuJAUchL_TrZ7m37M2lSN0Zjt2QrdBTtkvn_bP0W7dkvOiFQnIgtDV4REVMDrvedz-'

export default function Home() {
  const { count } = useCount()
  const location = useLocation()

  const goal = count?.goal ?? 2500
  const total = count?.total ?? null
  const pct = total != null ? Math.min(100, Math.round((total / goal) * 100)) : 0

  useEffect(() => {
    if (location.state?.scrollTo === 'connect' || location.hash === '#connect') {
      setTimeout(() => document.getElementById('connect')?.scrollIntoView({ behavior: 'smooth' }), 60)
    }
  }, [location])

  return (
    <>
      <section className="wrap hero">
        <div className="hero__grid">
          <div>
            <span className="eyebrow"><Icon name="star" filled /> America250 Initiative</span>
            <h1>The Heart of Jerome:<br /><em>Our Home for Kindness.</em></h1>
            <p className="hero__sub">
              We are Jerome's part of <b>America250</b> and Idaho's HCR&nbsp;22 resolution.
              Our goal is to foster <strong>{fmt(goal)} Acts of Kindness</strong> in the Magic
              Valley by July&nbsp;4,&nbsp;2026.
            </p>
            <div className="hero__cta">
              <Link to="/log" className="btn">Log Your Act</Link>
              <Link to="/ideas" className="btn btn--ghost">Get Ideas</Link>
            </div>
          </div>
          <div className="hero__media">
            <img src={HERO_IMG} alt="A charming Jerome, Idaho neighborhood street at golden hour with American flags and gardens" />
            <div className="counter-card counter-card--float">
              <p className="counter-card__label">Our Progress</p>
              <div className="counter-card__num">{fmt(total)}</div>
              <p className="counter-card__foot">Total Acts of Kindness Recorded</p>
              <div className="goal">
                <div className="goal__track"><div className="goal__fill" style={{ width: `${pct}%` }} /></div>
                <div className="goal__meta"><span>{pct}% of goal</span><span>{fmt(goal)} by Jul 4</span></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="section section--tint">
        <div className="wrap">
          <div className="bento">
            <div className="card card--span4">
              <h2>Cultivating Community Spirit</h2>
              <p>
                Kindness isn't just an action; it's the soil that grows a stronger society. From
                helping a neighbor with their garden to supporting local heritage, every act counts
                toward our {fmt(goal)} goal.
              </p>
              <p className="mt-lg">
                <a className="textlink" href="https://www.idahokindness.com/" target="_blank" rel="noopener">Visit IdahoKindness.com ↗</a>
                &nbsp;&nbsp;
                <Link className="textlink" to="/ideas">Get ideas →</Link>
              </p>
            </div>
            <div className="card card--span2 card--primary card--center">
              <span className="card__icon"><Icon name="sprout" /></span>
              <h3>Join the Movement</h3>
              <p>Be part of the Idaho HCR&nbsp;22 resolution and show the Heart of Jerome.</p>
              <Link to="/log" className="btn btn--ghost mt-lg">Log Your Act</Link>
            </div>
          </div>
        </div>
      </section>

      <section className="section">
        <div className="wrap">
          <div className="s-head">
            <h2>How to log an act</h2>
            <p>Every act, no matter how small, is a thread in the fabric of Jerome. Here's how to add yours.</p>
          </div>
          <div className="steps">
            <div className="step"><span className="step__n">1</span><p>Identify a moment of connection or service in your community.</p></div>
            <div className="step"><span className="step__n">2</span><p>Fill out the short form — it takes less than a minute.</p></div>
            <div className="step"><span className="step__n">3</span><p>Watch our community kindness counter grow toward {fmt(goal)}.</p></div>
          </div>
          <div className="center" style={{ marginTop: 'var(--space-2xl)' }}>
            <Link to="/log" className="btn">Log Your Act of Kindness</Link>
          </div>
        </div>
      </section>

      <section className="section connect" id="connect">
        <div className="wrap">
          <div className="s-head">
            <h2>Connect With Us</h2>
            <p>Have questions or want to get more involved in Jerome's kindness initiative? Reach out to our team leaders.</p>
          </div>
          <div className="contact-grid">
            <div className="contact">
              <h3>Dave Davis</h3>
              <a href="tel:2084207383"><Icon name="phone" /> (208) 420-7383</a><br />
              <a href="mailto:davidmbernice@gmail.com"><Icon name="mail" /> davidmbernice@gmail.com</a>
            </div>
            <div className="contact">
              <h3>Tim Knutson</h3>
              <a href="tel:4175761046"><Icon name="phone" /> (417) 576-1046</a><br />
              <a href="mailto:pastor@jeromebbc.com"><Icon name="mail" /> pastor@jeromebbc.com</a>
            </div>
          </div>
        </div>
      </section>
    </>
  )
}
