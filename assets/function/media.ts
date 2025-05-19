import {copyToClipboard} from "./browser";

export default function mediaCopy() {
    //events
    document.querySelectorAll('.content-to-copy').forEach((el) => {
        el.addEventListener('click', copy, true);
    })
    document.querySelectorAll('.img-to-copy').forEach((el) => {
        el.addEventListener('click', copy, true);
    })
    //copy
    async function copy(e: any) {
        const id = e.target.dataset.id;
        const media = e.target.dataset.media;
        document.querySelectorAll('.message-copy').forEach((message) => {
            if(message.classList.contains("copy")) {
                message.classList.remove("copy");
            }
        });
        const file = "/upload/medias/"+media;
        const copy = await copyToClipboard(file)
        if(copy) {
            const currentMessage = document.getElementById(id) as HTMLParagraphElement | null;
            currentMessage?.classList.add("copy");
        }
    }
}