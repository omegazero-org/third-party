/*
 * Copyright (C) 2023-2024 warp03
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
 * If a copy of the MPL was not distributed with this file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

(function(){

	injectScript("https://static.warpcs.org/p/js/tooltip.js");

	function injectScript(url){
		let s = document.createElement("script");
		s.src = url;
		document.head.append(s);
	}

})();
