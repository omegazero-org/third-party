
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
		let path = window.location.pathname.split("/");
		let mirrors = data[path[1] + "/" + path[2]];
		if(mirrors){
			let repoHeader = document.querySelector("div.page-content.repository div.ui.container div.repo-button-row");
			if(!repoHeader)
				return;
			addRepoLink(repoHeader, mirrors, path.slice(3, 6), path.slice(6).join("/"));
		}else{
			let listItems = document.querySelectorAll("div.repositories.explore div.flex-item");
				console.log(listItems);
			for(let item of listItems){
				let name = item.querySelector("div.flex-item-title a.name").getAttribute("href").substring(1);
				console.log(name);
				let mirrors = data[name];
				if(!mirrors)
					continue;
				addExploreLink(item, mirrors);
			}
		}
	}

	function addExploreLink(item, mirrors){
		let descEl = item.querySelector("div.flex-item-title");
		//descEl.childNodes[1].style.display = "inline-block";
		//descEl.insertBefore(document.createElement("br"), descEl.childNodes[3]);
		let linkContainer = document.createElement("div");
		//linkContainer.style.float = "right";
		//linkContainer.style["text-align"] = "right";
		let mcHtml = "";
		for(let mirrorType in mirrors){
			let text = mirrorTypeText[mirrorType];
			if(!text)
				continue;
			mcHtml += '<a class="ui button" style="display: inline-block;padding: 2px;font-size: small;" href="' + mirrors[mirrorType] + '">' + text + '</a>';
		}
		linkContainer.innerHTML = mcHtml;
		descEl.append(linkContainer);
	}

	function addRepoLink(headerEl, mirrors, sub, path){
		let buttonsWrapEl = document.createElement("div");
		buttonsWrapEl.classList = "df ac";
		for(let mirrorType in mirrors){
			let text = mirrorTypeText[mirrorType];
			if(!text)
				continue;
			let href = mirrors[mirrorType];
			if(sub && sub[0] == "src"){
				if(!href.endsWith("/"))
					href += "/";
				if(mirrorType == "omz-source"){
					if(sub[2] != "master")
						continue;
					href += path;
				}else if(mirrorType == "github"){
					href += "tree/" + sub[2] + "/" + path;
				}else
					href += sub + "/" + path;
			}
			buttonsWrapEl.innerHTML += '<a class="ui compact basic button" href="' + href + '">' + text + '</a>';
		}
		headerEl.insertBefore(buttonsWrapEl, headerEl.childNodes[2]);
	}

	window.addEventListener("DOMContentLoaded", init);

})();

