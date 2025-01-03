/*
 * Copyright (C) 2023-2024 Wilton Arthur Poth
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
 * If a copy of the MPL was not distributed with this file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

(function(){
	window.addEventListener("rl-view-model", (e) => {
		if(e.detail && e.detail.viewModelTemplateID === "Login"){
			let clicked = false;
			const LoginUserView = e.detail;
			LoginUserView.submitCommand = (self, event) => {
				if(clicked)
					return;
				clicked = true;
				try{
					LoginUserView.submitRequest(true);
				}catch(e){
					// this throws an error, idk why, but it does still enable the spinning wheel which was the goal
				}
				window.location = "?OAuthEndpoint";
				return true;
			};
		}
		let logoutbt = document.querySelector("[data-bind='click: logoutClick']");
		if(logoutbt){
			let logoutbtn = logoutbt.cloneNode(true);
			logoutbt.parentNode.replaceChild(logoutbtn, logoutbt);
			delete logoutbtn.dataset.bind;
			logoutbtn.addEventListener("click", (event) => {
				window.location = "https://idp.warpcs.org/logout/?flcid=00000000acc47584080e740529d4b3fd";
			});
		}
	});
})();
