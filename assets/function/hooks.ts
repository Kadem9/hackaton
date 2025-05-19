import {useEffect} from "preact/compat";

export function useAsyncEffect(fn: Function, deps = []) {
  useEffect(() => {
    fn()
  }, deps)
}
