type FetchParams = Record<string, any>
export async function jsonFetch (url: string, params:FetchParams = {}) {
  if (params.body instanceof FormData) {
    params.body = Object.fromEntries(params.body)
  }
  if (params.body && typeof params.body === 'object') {
    params.body = JSON.stringify(params.body)
  }
  if(params.body !== undefined && params.method === undefined) {
    params.method = 'POST'
  }
  params = {
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
    ...params
  }

  const response = await fetch(url, params)
  if (response.status === 204) {
    return null
  }
  const data = await response.json()
  if (response.ok) {
    return data
  }
  return null
}