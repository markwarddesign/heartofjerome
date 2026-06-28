import { createContext, useCallback, useContext, useEffect, useState } from 'react'
import { Route, Routes, useLocation } from 'react-router-dom'
import Header from './components/Header.jsx'
import Footer from './components/Footer.jsx'
import Home from './pages/Home.jsx'
import Ideas from './pages/Ideas.jsx'
import Log from './pages/Log.jsx'
import { getCount } from './api.js'

const CountCtx = createContext(null)
export const useCount = () => useContext(CountCtx)

function ScrollManager() {
  const location = useLocation()
  useEffect(() => {
    if (!location.hash) window.scrollTo({ top: 0 })
  }, [location.pathname, location.hash])
  return null
}

export default function App() {
  const [count, setCount] = useState(null)

  const refresh = useCallback(async () => {
    try {
      setCount(await getCount())
    } catch {
      /* counter stays null → UI shows a dash */
    }
  }, [])

  useEffect(() => {
    refresh()
  }, [refresh])

  return (
    <CountCtx.Provider value={{ count, setCount, refresh }}>
      <ScrollManager />
      <Header />
      <main>
        <Routes>
          <Route path="/" element={<Home />} />
          <Route path="/ideas" element={<Ideas />} />
          <Route path="/log" element={<Log />} />
          <Route path="*" element={<Home />} />
        </Routes>
      </main>
      <Footer />
    </CountCtx.Provider>
  )
}
