export default function sidebar() {
    /* nav open/close */
    const dashboard = document.getElementById("app-dashboard") as HTMLDivElement | null;
    const navBtn = document.getElementById("sidebar-btn") as HTMLButtonElement | null;

    if(navBtn != null && dashboard != null) {
        navBtn.addEventListener("click", toggleNav);
    }

    function toggleNav() {
        if(dashboard != null) {
            //open
            if (dashboard.classList.contains("close")) {
                dashboard.classList.remove("close");
            }
            //close
            else {
                dashboard.classList.add("close");
            }
        }
    }
}