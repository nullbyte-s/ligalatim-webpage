function carregarPagina(pagina) {
	return new Promise((resolve, reject) => {
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function () {
			if (this.readyState == 4) {
				if (this.status == 200) {
					document.getElementById("content").innerHTML = this.responseText;
					window.scrollTo(0, 0);
					resolve();
					$(document).ready(function () {
						var zindex = 10;
						$("div.card").click(function (e) {
							e.preventDefault();
							var isShowing = false;

							if ($(this).hasClass("show")) {
								isShowing = true
							}
							if ($("div.cards").hasClass("showing")) {
								$("div.card.show")
									.removeClass("show");
								if (isShowing) {
									$("div.cards")
										.removeClass("showing");
								} else {
									$(this)
										.css({ zIndex: zindex })
										.addClass("show");
								}
								zindex++;
							} else {
								$("div.cards")
									.addClass("showing");
								$(this)
									.css({ zIndex: zindex })
									.addClass("show");
								zindex++;
							}
						});
					});
				} else {
					reject(new Error(`Erro ao carregar a página: ${pagina}`));
				}
			}
		};

		xhttp.open("GET", "includes/" + pagina, true);
		xhttp.send();
	});
}
