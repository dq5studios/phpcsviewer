class phpcsConfig {
    constructor() {
        //
    }

    public pageReady(): void {
        document.querySelectorAll("h4 [type=checkbox]").forEach((check: HTMLInputElement) => {
            check.addEventListener("change", page.toggleSniff);
        });
    }

    public toggleSniff(event: Event): void {
        let active = <HTMLInputElement>event.target;
        let sniff_list = active.parentNode.parentNode.parentNode.querySelector("dl");
        if (active.checked) {
            sniff_list.removeAttribute("hidden");
            sniff_list.querySelectorAll("[type=checkbox]").forEach((check: HTMLInputElement) => {
                check.checked = true;
            });
        } else {
            sniff_list.setAttribute("hidden", "hidden");
            sniff_list.querySelectorAll("[type=checkbox]").forEach((check: HTMLInputElement) => {
                check.checked = false;
            });
        }
    }
}

let page = new phpcsConfig();

window.addEventListener("DOMContentLoaded", page.pageReady);
