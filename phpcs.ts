class phpcsConfig {
    public pageReady(): void {
        document.querySelectorAll("h4 [type=checkbox]").forEach((check: HTMLInputElement) => {
            check.addEventListener("change", page.toggleSniff);
        });
        document.querySelectorAll(".example_toggle").forEach((check: HTMLInputElement) => {
            $(check).popover({
                trigger: "hover focus",
                html: true,
                sanitize: false,
                content: check.parentNode.querySelector(".examples").outerHTML,
            });
        });
        document.getElementById("search_filter").addEventListener("input", page.filterSniffs);
        document.getElementById("enabled_only").addEventListener("change", page.toggleEnabled)
        document.getElementById("import").addEventListener("click", page.importXml);
        document.getElementById("export").addEventListener("click", page.exportXml);
    }

    public toggleSniff(event: Event): void {
        let active = <HTMLInputElement>event.target;
        let sniff_list = active.parentNode.parentNode.parentNode.querySelector(".subs");
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

    public toggleEnabled(event: Event): void {
        let active = <HTMLInputElement>document.getElementById("enabled_only");
        if (active.checked) {
            let show_matches = document.querySelectorAll("h4 [type=checkbox]:checked");
            if (show_matches.length === 0) {
                return;
            }
            let hide_matches = document.querySelectorAll("[data-sniff]");
            hide_matches.forEach((element: HTMLElement) => {
                element.setAttribute("hidden", "hidden");
            });
            show_matches.forEach((element: HTMLElement) => {
                let sniff = <HTMLElement>element.parentNode.parentNode.parentNode.parentNode;
                sniff.removeAttribute("hidden");
            });
        } else {
            let hide_matches = document.querySelectorAll("[data-sniff]");
            hide_matches.forEach((element: HTMLElement) => {
                element.removeAttribute("hidden");
            });
        }
    }

    public importXml() {

    }

    public exportXml() {
        let xml_doc = document.implementation.createDocument(null, "ruleset", null);
        let nl = xml_doc.createTextNode("\n");
        let tab = xml_doc.createTextNode("\t");

        document.querySelectorAll("h4 [type=checkbox]:checked").forEach((check: HTMLInputElement) => {
            let rule = xml_doc.createElement("rule");
            let ruleset = xml_doc.getElementsByTagName("ruleset");
            rule.setAttribute("ref", check.getAttribute("name"));
            let sniff = check.parentNode.parentNode.parentNode;
            sniff.querySelectorAll(".rules [type=radio]:checked").forEach((sub_rule: HTMLInputElement) => {
                switch (sub_rule.value) {
                    case "off":
                        let exclude = xml_doc.createElement("exclude");
                        exclude.setAttribute("name", sub_rule.getAttribute("name"));
                        rule.appendChild(exclude);
                        break;
                    case "warning":
                        let rule_warn = xml_doc.createElement("rule");
                        rule_warn.setAttribute("ref", sub_rule.getAttribute("name"));
                        let type = xml_doc.createElement("type");
                        type.textContent = "warning";
                        rule_warn.appendChild(type);
                        ruleset[0].appendChild(rule_warn);
                        break;
                }
            });
            sniff.querySelectorAll(".property");
            ruleset[0].appendChild(rule);
        });

        let serializer = new XMLSerializer();
        let xml_string = serializer.serializeToString(xml_doc);
        xml_string = xml_string.replace(/></g, ">\n<");
        xml_string = xml_string.replace(/\n\n/g, "\n");

        console.log("<?xml version=\"1.0\"?>\n" + xml_string);
    }
}

let page = new phpcsConfig();

window.addEventListener("DOMContentLoaded", page.pageReady);
