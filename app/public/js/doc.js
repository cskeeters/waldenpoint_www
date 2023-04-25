function findElementWithId(element) {
    console.log("findElementWithId: "+element.tagName);
    console.log("id: "+element.getAttribute("id"));

    if (element.getAttribute("id") != null) {
        return element;
    }

    if (element.parentNode != null) {
        return findElementWithId(element.parentNode);
    }

    return null;
}

function copyLink(event) {
    const elementWithId = findElementWithId(event.target);

    if (elementWithId == null) {
        console.log("could not find elementWithId");
        return;
    }

    const id = elementWithId.getAttribute("id");
    const url = location.protocol + '//' + location.host + location.pathname + "#" + id;

    navigator.clipboard.writeText(url).then(
        () => {
            // alert("Link copied");
        },
        () => {
            // alert("Permission denied");
        }
    );
    location.href = url;
}

var toHide = null; // elementWithId
var timeoutId = null;
var hideId = null;

function headingOver(event) {
    console.log("over"); //FIXME

    const elementWithId = findElementWithId(event.target);

    if (elementWithId == null) {
        console.log("could not find elementWithId");
        return;
    }

    if (toHide != null) {
        console.log("Clearing timeout"); //FIXME
        clearTimeout(timeoutId);
        if (toHide != elementWithId) {
            hideLink();
        }
    }
    const link = elementWithId.querySelector('.link');
    console.log(link)

    if (link != null) link.style.visibility = "visible";
}

function hideLink() {
    console.log("hideLink"); //FIXME
    if (toHide == null) return;
    const link = toHide.querySelector('.link');
    if (link != null) {
        link.style.visibility = "hidden";
    }
    toHide = null;
}

function headingOut(event) {
    console.log("out"); //FIXME
    toHide = findElementWithId(event.target);
    timeoutId = setTimeout(hideLink, 200);
}



function addLinksFor(tagname) {
    const h2s = document.getElementsByTagName(tagname);
    console.log("Found "+h2s.length+" h2s");

    for (let i=0; i<h2s.length; i++) {
        let heading = h2s[i];
        let id = heading.getAttribute("id");
        if (id != null) {
            console.log("adding link for "+id);

            let svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
            svg.setAttribute("class", "link"); /* for css */
            svg.setAttribute("viewBox", "0 0 16 16");
            svg.setAttribute("width", "16");
            svg.setAttribute("height", "16");

            let path = document.createElementNS("http://www.w3.org/2000/svg", "path");
            path.setAttribute("d", "m7.775 3.275 1.25-1.25a3.5 3.5 0 1 1 4.95 4.95l-2.5 2.5a3.5 3.5 0 0 1-4.95 0 .751.751 0 0 1 .018-1.042.751.751 0 0 1 1.042-.018 1.998 1.998 0 0 0 2.83 0l2.5-2.5a2.002 2.002 0 0 0-2.83-2.83l-1.25 1.25a.751.751 0 0 1-1.042-.018.751.751 0 0 1-.018-1.042Zm-4.69 9.64a1.998 1.998 0 0 0 2.83 0l1.25-1.25a.751.751 0 0 1 1.042.018.751.751 0 0 1 .018 1.042l-1.25 1.25a3.5 3.5 0 1 1-4.95-4.95l2.5-2.5a3.5 3.5 0 0 1 4.95 0 .751.751 0 0 1-.018 1.042.751.751 0 0 1-1.042.018 1.998 1.998 0 0 0-2.83 0l-2.5 2.5a1.998 1.998 0 0 0 0 2.83Z");
            svg.appendChild(path);

            heading.insertBefore(svg, heading.firstChild);
            svg.addEventListener("click", copyLink);
            heading.addEventListener("mouseover", headingOver);
            heading.addEventListener("mouseout", headingOut);
        }
    }
}

function addLinks() {
    addLinksFor("h2");
    addLinksFor("p");
}

window.onload = addLinks;
