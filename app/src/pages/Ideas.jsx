import { Link } from 'react-router-dom'
import Icon from '../components/Icon.jsx'
import Seo from '../components/Seo.jsx'
import { useCount } from '../App.jsx'

const fmt = (n) => (n == null ? '2,500' : n.toLocaleString('en-US'))

const IDEAS_IMG =
  'https://lh3.googleusercontent.com/aida-public/AB6AXuAICtC3acNW-FrEhxKRj786blUZyJEzrYCw_KFufzpRLFkTPkRwOalzo9sW5ynR4E7hFV3NjHfx1HOy5ma3ALM5-ct03NhpHEmrkxv245tjock3KKBTDN5A5Nj7hQrR9bfKmDs2cs4PIEMQdl12WxXtv_WeIvpkVCURUeMaiOiF3A8pIu3zAiQrT5R-FTG2LabePDAz4kQralMOk2-36jTEGg-l6YlEEvDCILvRXSvWIRrB5JIevlcjZKkRYt2XWBb-P59GwnLsj9Fg'

export default function Ideas() {
  const { count } = useCount()
  const goal = fmt(count?.goal)

  return (
    <>
      <Seo
        title="Kindness Ideas | The Heart of Jerome"
        description="Simple, practical ways to spread kindness in Jerome and the Magic Valley. Get inspired, then log your act toward our America250 goal."
        path="/ideas"
      />
      <section className="wrap hero">
        <div className="hero__grid">
          <div>
            <span className="eyebrow"><Icon name="star" filled /> America250 Celebration</span>
            <h1>Inspiration for <em>Kindness.</em></h1>
            <p className="hero__sub">
              Simple ways to make a big difference in the Magic Valley. Every small act plants a
              seed for our future.
            </p>
            <div className="hero__cta">
              <Link to="/log" className="btn">Log Your Act</Link>
              <Link to="/" state={{ scrollTo: 'connect' }} className="btn btn--ghost">Connect</Link>
            </div>
          </div>
          <div className="hero__media">
            <img src={IDEAS_IMG} alt="Community volunteers planting flowers in a sun-drenched Idaho park" />
          </div>
        </div>
      </section>

      <section className="section section--tint">
        <div className="wrap">
          <div className="bento">
            <div className="card card--span4">
              <span className="card__icon" style={{ color: 'var(--color-tertiary)' }}><Icon name="home" /></span>
              <h3>For Neighbors</h3>
              <ul>
                <li>Mow a lawn for someone down the street</li>
                <li>Help an elder carry their groceries</li>
                <li>Share surplus from your vegetable garden</li>
                <li>Shovel a driveway or rake leaves</li>
              </ul>
            </div>

            <div className="card card--span2 card--secondary">
              <span className="card__icon"><Icon name="school" /></span>
              <h3>For Schools</h3>
              <ul>
                <li>Donate classroom supplies</li>
                <li>Write a thank-you note to a teacher</li>
                <li>Volunteer as a reading mentor</li>
              </ul>
            </div>

            <div className="card card--span3 card--tertiary">
              <span className="card__icon" style={{ color: 'var(--color-tertiary)' }}><Icon name="users" /></span>
              <h3>For the Community</h3>
              <p style={{ fontStyle: 'italic' }}>"We make a living by what we get, but we make a life by what we give."</p>
              <ul>
                <li>Clean up a local park</li>
                <li>Volunteer at the food bank</li>
                <li>Pick up litter along a trail</li>
              </ul>
            </div>

            <div className="card card--span3">
              <span className="card__icon" style={{ color: 'var(--color-primary)' }}><Icon name="book" /></span>
              <h3>For Local History</h3>
              <p>Our past informs our future. Help us preserve the stories that built Jerome.</p>
              <ul>
                <li>Record a story from an elder</li>
                <li>Help preserve a local landmark</li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <section className="section center">
        <div className="wrap" style={{ maxWidth: '40rem' }}>
          <span className="card__icon" style={{ color: 'var(--color-primary)', marginInline: 'auto' }}><Icon name="heart" /></span>
          <h2 style={{ fontSize: 'var(--text-4xl)', marginTop: 'var(--space-md)' }}>Ready to share your kindness?</h2>
          <p className="lede" style={{ margin: 'var(--space-md) auto var(--space-xl)', maxWidth: '46ch' }}>
            Every act counts toward our goal of {goal} acts of kindness for America250.
          </p>
          <Link to="/log" className="btn">Log My Act</Link>
        </div>
      </section>
    </>
  )
}
