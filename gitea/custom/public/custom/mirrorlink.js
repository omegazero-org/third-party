
(function(){

	const mirrorTypeText = {
		"omz-source": "View on source.omegazero.org",
		"github": "GitHub mirror"
	};

	function init(){
		fetch("https://api.omegazero.org/v2/git/mirrors").then((res) => {
			return res.json();
		}).then((json) => {
			addLinks(json);
		});
	}

	function addLinks(data){
		let path = window.location.pathname;
		let mirrors = data[window.location.pathname.substring(1)];
		if(mirrors){
			let repoHeader = document.querySelector("div.page-content.repository div.ui.container div.secondary.menu");
			if(!repoHeader)
				return;
			addRepoLink(repoHeader, mirrors);
		}else{
			let listItems = document.querySelectorAll("div.repository.list div.item");
			for(let item of listItems){
				let name = item.querySelector("div.header div.repo-title a").getAttribute("href").substring(1);
				let mirrors = data[name];
				if(!mirrors)
					continue;
				addExploreLink(item, mirrors);
			}
		}
	}

	function addExploreLink(item, mirrors){
		let descEl = item.querySelector("div.description");
		descEl.childNodes[1].style.display = "inline-block";
		descEl.insertBefore(document.createElement("br"), descEl.childNodes[3]);
		let linkContainer = document.createElement("div");
		linkContainer.style.float = "right";
		linkContainer.style["text-align"] = "right";
		let mcHtml = "";
		for(let mirrorType in mirrors){
			let text = mirrorTypeText[mirrorType];
			if(!text)
				continue;
			mcHtml += '<a class="ui primary button" style="display: inline-block;padding: 5px;margin: 2.5px;" href="' + mirrors[mirrorType] + '">' + text + '</a>';
		}
		linkContainer.innerHTML = mcHtml;
		descEl.prepend(linkContainer);
	}

	function addRepoLink(headerEl, mirrors){
		let buttonsWrapEl = document.createElement("div");
		buttonsWrapEl.classList = "fitted item mx-0";
		let buttonsEl = document.createElement("div");
		buttonsEl.classList = "ui tiny primary buttons";
		for(let mirrorType in mirrors){
			let text = mirrorTypeText[mirrorType];
			if(!text)
				continue;
			buttonsEl.innerHTML += '<a class="ui button" href="' + mirrors[mirrorType] + '">' + text + '</a>';
		}
		buttonsWrapEl.append(buttonsEl);
		headerEl.insertBefore(buttonsWrapEl, headerEl.childNodes[6]);
	}

	window.addEventListener("DOMContentLoaded", init);

})();

