class phpcsConfig {
    constructor() {
        //
    }

    public pageReady(): void {
        document.querySelectorAll("h4 [type=checkbox]").forEach((check: HTMLInputElement) => {
            check.addEventListener("change", page.toggleSniff);
        });
        document.querySelectorAll(".example_toggle").forEach((check: HTMLInputElement) => {
            check.addEventListener("click", page.toggleExample);
        });
        document.getElementById("search_filter").addEventListener("input", page.filterSniffs);
    }

    public toggleSniff(event: Event): void {
        let active = <HTMLInputElement>event.target;
        let sniff_list = active.parentNode.parentNode.parentNode.querySelector("dl");
        if (active.checked) {
            sniff_list.removeAttribute("hidden");
            sniff_list.querySelectorAll(".rules [type=checkbox]").forEach((check: HTMLInputElement) => {
                check.checked = true;
            });
        } else {
            sniff_list.setAttribute("hidden", "hidden");
            sniff_list.querySelectorAll(".rules [type=checkbox]").forEach((check: HTMLInputElement) => {
                check.checked = false;
            });
        }
    }

    public toggleExample(event: Event): void {
        let active = <HTMLInputElement>event.target;
        let examples = active.parentNode.parentNode.parentNode.parentNode.querySelector(".examples");
        if (active.classList.contains("text-muted")) {
            active.classList.add("text-primary");
            active.classList.remove("text-muted");
            examples.removeAttribute("hidden");
        } else {
            active.classList.add("text-muted");
            active.classList.remove("text-primary");
            examples.setAttribute("hidden", "hidden");
        }
    }

    public filterSniffs(event: Event): void {
        let search_box = <HTMLInputElement>document.getElementById("search_filter");
        let search_string = search_box.value.toLowerCase();
        if (search_string === "") {
            let show_matches = document.querySelectorAll("[data-sniff]");
            show_matches.forEach((element: HTMLElement) => {
                element.removeAttribute("hidden");
            });
            return;
        }
        let show_matches = document.querySelectorAll(`[data-sniff*="${search_string}" i]`);
        show_matches.forEach((element: HTMLElement) => {
            element.removeAttribute("hidden");
        });
        let hide_matches = document.querySelectorAll(`[data-sniff]:not([data-sniff*="${search_string}" i])`);
        hide_matches.forEach((element: HTMLElement) => {
            element.setAttribute("hidden", "hidden");
        });
    }
}

let page = new phpcsConfig();

window.addEventListener("DOMContentLoaded", page.pageReady);
