"use client"

import { useEffect } from "react"

export default function ErrorBoundary({
  error,
  reset,
}: {
  error: Error & { digest?: string }
  reset: () => void
}) {
  useEffect(() => {
    // Log the error to an error reporting service
    console.error("Vercel App Error:", error)
  }, [error])

  return (
    <div className="flex h-screen w-full flex-col items-center justify-center bg-red-50 p-6">
      <h2 className="text-2xl font-bold text-red-600 mb-4">Something went wrong!</h2>
      <div className="bg-white p-4 rounded-md shadow-sm border border-red-200 text-red-800 max-w-2xl w-full font-mono text-sm overflow-auto">
        <p className="font-bold mb-2">Error Message:</p>
        <p>{error.message}</p>
        {error.digest && <p className="mt-2 text-xs text-slate-500">Digest: {error.digest}</p>}
      </div>
      <button
        className="mt-6 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors"
        onClick={() => reset()}
      >
        Try again
      </button>
    </div>
  )
}
