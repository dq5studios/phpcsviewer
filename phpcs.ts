class phpcsConfig {
    public pageReady(): void {
        document.querySelectorAll("h4 [type=radio]").forEach((check: HTMLInputElement) => {
            check.addEventListener("change", page.toggleSniff);
        });
        document.querySelectorAll(".example_toggle").forEach((check: HTMLInputElement) => {
            $(check).popover({
                trigger: "hover focus",
                html: true,
                sanitize: false,
                content: check.parentElement.querySelector(".examples").outerHTML,
            });
        });
        document.getElementById("search_filter").addEventListener("input", page.filterSniffs);
        document.getElementById("enabled_only").addEventListener("change", page.toggleEnabled)
        document.getElementById("import").addEventListener("click", page.importXml);
        document.getElementById("export").addEventListener("click", page.exportXml);
    }

    public toggleSniff(event: Event): void {
        let active = <HTMLInputElement>event.target;
        active.parentElement.parentElement.querySelector(".active").classList.remove("active");
        active.parentElement.classList.add("active");
        let sniff_list = active.parentElement.parentElement.parentElement.parentElement.querySelector(".subs");
        if (active.value !== "off") {
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
            let show_matches = document.querySelectorAll("h4 [type=radio]:not([value=off]):checked");
            if (show_matches.length === 0) {
                return;
            }
            let hide_matches = document.querySelectorAll("[data-sniff]");
            hide_matches.forEach((element: HTMLElement) => {
                element.setAttribute("hidden", "hidden");
            });
            show_matches.forEach((element: HTMLElement) => {
                let sniff = <HTMLElement>element.parentElement.parentElement.parentElement.parentElement.parentElement;
                sniff.removeAttribute("hidden");
            });
        } else {
            let hide_matches = document.querySelectorAll("[data-sniff]");
            hide_matches.forEach((element: HTMLElement) => {
                element.removeAttribute("hidden");
            });
        }
    }

    public importXml(): void {

    }

    public exportXml(): boolean {
        let enabled_sniffs = document.querySelectorAll("h4 [type=radio]:not([value=off]):checked");
        if (enabled_sniffs.length == 0) {
            return false;
        }

        // Setup XML document
        let xml_doc = document.implementation.createDocument(null, "ruleset", null);
        let nl = xml_doc.createTextNode("\n");
        let tab = xml_doc.createTextNode("\t");

        // Loop over enabled
        enabled_sniffs.forEach((check: HTMLInputElement) => {
            let rule = xml_doc.createElement("rule");
            let ruleset = xml_doc.getElementsByTagName("ruleset");
            rule.setAttribute("ref", check.getAttribute("name"));
            if (check.value == "warning") {
                let type = xml_doc.createElement("type");
                type.textContent = "warning";
                rule.appendChild(type);
            }
            let sniff = <Element>check.parentElement.parentElement.parentElement.parentElement;
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
            let props = sniff.querySelectorAll(".property input");
            if (props.length > 0) {
                let properties = xml_doc.createElement("properties");
                let p_cnt = 0;
                props.forEach((prop: HTMLInputElement) => {
                    if (prop.getAttribute("data-original") == prop.value) {
                        return;
                    }
                    let property = xml_doc.createElement("property");
                    property.setAttribute("name", prop.getAttribute("name").replace(check.getAttribute("name") + ".", ""));
                    let value = prop.value;
                    if (prop.type == "checkbox") {
                        value = prop.checked.toString();
                        if (value == prop.getAttribute("data-original")) {
                            return;
                        }
                    }
                    property.setAttribute("value", value);
                    properties.appendChild(property);
                    p_cnt++;
                });
                if (p_cnt > 0) {
                    rule.appendChild(properties);
                }
            }
            ruleset[0].appendChild(rule);
        });

        // Serialize and add indent
        let serializer = new XMLSerializer();
        let xml_string = serializer.serializeToString(xml_doc);
        xml_string = xml_string.replace(/></g, ">\n<");
        xml_string = xml_string.replace(/\n\n/g, "\n");
        xml_string = xml_string.replace(/<(\/)?rule([^s])/g, "\t<$1rule$2");
        xml_string = xml_string.replace(/<(\/)?type/g, "\t\t<$1type");
        xml_string = xml_string.replace(/<(\/)?properties/g, "\t\t<$1properties");
        xml_string = xml_string.replace(/<property/g, "\t\t\t<property");
        xml_string = "<?xml version=\"1.0\"?>\n" + xml_string;

        // Serve up as file
        let link = <HTMLLinkElement>document.getElementById("export");
        link.setAttribute("download", "phpcs.xml");
        let data = new Blob([xml_string], {type: "application/xml"});
        let file_obj = window.URL.createObjectURL(data);
        link.href = file_obj;
    }
}

let page = new phpcsConfig();

window.addEventListener("DOMContentLoaded", page.pageReady);
