<!DOCTYPE html>
<html>
  <head>
    <title>Gaming</title>
    <link type="text/css" rel="stylesheet" href="style.css" />
  </head>
  <body>
    <div id="statystyki">
      <input id="nickname" onchange="nickChange()" />
      <br /><br />
      <?php
      $conn = new mysqli("db4free.net", "root", "yQtu5uX*#yj-kN*", "statystyki");

      // Sprawdzenie połączenia
      if ($conn->connect_error) { die("Błąd połączenia: " .
      $conn->connect_error); } // Zapytanie SQL dla pobrania 10 największych wyników 
      $sql = "SELECT nazwa, wynik FROM statystyki ORDER BY wynik DESC
      LIMIT 10"; $result = $conn->query($sql); // Tworzenie tabeli HTML 
      if ($result->num_rows > 0) { echo "
      <table>
        "; echo "
        <tr>
          <th>Nazwa</th>
          <th>Wynik</th>
        </tr>
        "; // Wyświetlanie wyników w tabeli 
        while ($row = $result->fetch_assoc()) { echo "
        <tr>
          <td>" . $row["nazwa"] . "</td>
          <td>" . $row["wynik"] . "</td>
        </tr>
        "; } echo "
      </table>
      "; } else { echo "Brak wyników."; } // Zamykanie połączenia z bazą danych
      $conn->close(); ?>
    </div>

    <div class="game-container">
      <h1 id="score">:)</h1>
      <div class="game-frame">
        <canvas id="gameCanvas" width="800" height="800"></canvas>
      </div>
    </div>

    <script>
      // Pobieranie elementu canvas
      var canvas = document.getElementById("gameCanvas");
      var ctx = canvas.getContext("2d");
      var xhr = new XMLHttpRequest();
      xhr.open("POST", "index.php", true);

      // Minimalny i maksymalny rozmiar kształtów
      var minShapeSize = Math.min(canvas.width, canvas.height) / 22;
      var maxShapeSize = Math.min(canvas.width, canvas.height) / 16;

      // Tablica przechowująca kształty
      var shapes = [];

      var enemySpawnInterval;

      var gameActive = true;

      let enemySpawnTime = 1000;

      var trailPositions = [];

      var maxTrailPositions = 30;

    // Funkcja rysująca trail
    function drawTrail() {
        ctx.globalAlpha = 0.05; // Ustawienie przezroczystości
        for (var i = 0; i < trailPositions.length; i++) {
            var pos = trailPositions[i];
            ctx.beginPath();
            ctx.arc(pos.x, pos.y, cursorBall.radius, 0, 2 * Math.PI);
            ctx.fillStyle = "white";
            ctx.fill();
            ctx.closePath();
        }
        ctx.globalAlpha = 1; // Przywrócenie normalnej przezroczystości
    }

      function startEnemySpawnInterval() {
        enemySpawnTime -= (score * 2);
        enemySpawnInterval = setInterval(function () {
          createRandomShape();
        }, enemySpawnTime);
      }

      // Funkcja zatrzymująca zwiększanie wyniku, gdy gra jest nieaktywna
      function pauseGame() {
        gameActive = false;
      }

      // Funkcja wznawiająca zwiększanie wyniku, gdy gra jest aktywna
      function resumeGame() {
        gameActive = true;
      }

      let nickname;

      function nickChange() {
        var nicknameInput = document.getElementById("nickname");
        nickname = nicknameInput.value;
        if (nickname.length < 4) {
          var randomNumber = Math.floor(Math.random() * 1000) + 1;
          nickname = "gracz" + randomNumber;
          nicknameInput.value = nickname;
        }
      }

      // Kulka podążająca za kursorem
      var cursorBall = {
        x: canvas.width / 2,
        y: canvas.height / 2,
        radius: 20,
      };

      function sendScoreToPHP(score, nick) {
        var xhr = new XMLHttpRequest();
        var url = "statystyki.php"; // Ścieżka do pliku PHP obsługującego zapis statystyk
        var params = "score=" + score + "&nick=" + nick;

        xhr.open("POST", url, true);
        xhr.setRequestHeader(
          "Content-type",
          "application/x-www-form-urlencoded"
        );

        xhr.onreadystatechange = function () {
          if (xhr.readyState === 4 && xhr.status === 200) {
            console.log("Wynik został zapisany." + score + " " + nick);
          }
        };

        xhr.send(params);
      }

      // Zmienne dotyczące wyniku gry
      var score = 0;
      var scoreIncrement = 1;
      var scoreUpdateTime = 2000; // Czas (w milisekundach) do zwiększania wyniku

      // Funkcja zwiększająca wynik gry
      function increaseScore() {
        if (gameActive) {
          score += scoreIncrement;
        }
        updateScoreDisplay();
      }

      // Funkcja aktualizująca wyświetlanie wyniku
      function updateScoreDisplay() {
        var scoreElement = document.getElementById("score");
        scoreElement.textContent = "Score: " + score;
      }

      // Tworzenie losowego kształtu (kwadrat lub trójkąt) poza canvasem
      function createRandomShape() {
        var shape = {};
        shape.x =
          Math.random() < 0.5 ? -maxShapeSize : canvas.width + maxShapeSize; // Losowa pozycja x poza canvasem
        shape.y = Math.random() * canvas.height; // Losowa pozycja y w obrębie canvasu
        shape.vx = (Math.random() + 0.5) * (shape.x < 0 ? 1 : -1); // Losowa prędkość w osi x
        shape.vy = Math.random() * 2 - 1; // Losowa prędkość w osi y
        shape.size =
          Math.random() * (maxShapeSize - minShapeSize) + minShapeSize; // Losowy rozmiar kształtu

        if (Math.random() < 0.5) {
          shape.type = "square";
          shape.speedModifier = 2.5; // Prędkość dla kwadratów
        } else {
          shape.type = "triangle";
          shape.speedModifier = 3.5; // Prędkość dla trójkątów
        }

        // Obliczanie kąta pomiędzy kształtem a kursorem
        var angle = Math.atan2(cursorBall.y - shape.y, cursorBall.x - shape.x);

        // Ustalanie prędkości kształtu wzdłuż kąta
        shape.vx = Math.cos(angle) * shape.speedModifier;
        shape.vy = Math.sin(angle) * shape.speedModifier;

        shapes.push(shape);
      }

      // Funkcja rysująca pojedynczy kształt
      function drawShape(shape) {
        ctx.beginPath();
        if (shape.type === "square") {
          ctx.rect(shape.x, shape.y, shape.size, shape.size);
        } else if (shape.type === "triangle") {
          ctx.moveTo(shape.x, shape.y - shape.size / 2);
          ctx.lineTo(shape.x + shape.size / 2, shape.y + shape.size / 2);
          ctx.lineTo(shape.x - shape.size / 2, shape.y + shape.size / 2);
          ctx.closePath();
        }
        ctx.fillStyle = "red";
        ctx.fill();
      }

      // Funkcja rysująca białą kulę podążającą za kursorem
      function drawCursorBall() {
        ctx.beginPath();
        ctx.arc(cursorBall.x, cursorBall.y, cursorBall.radius, 0, 2 * Math.PI);
        ctx.fillStyle = "white";
        ctx.fill();
        ctx.closePath();
      }

      // Aktualizacja pozycji i rysowanie wszystkich kształtów
      function update() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Dodawanie aktualnej pozycji kulki do trailu
        trailPositions.push({ x: cursorBall.x, y: cursorBall.y });

        // Usuwanie starszych pozycji z trailu
        if (trailPositions.length > maxTrailPositions) {
            trailPositions.shift();
        }

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        for (var i = 0; i < shapes.length; i++) {
          var shape = shapes[i];
          shape.x += shape.vx * shape.speedModifier;
          shape.y += shape.vy * shape.speedModifier;

          // Odbijanie się od ścian canvasu
          if (
            shape.x < -maxShapeSize ||
            shape.x > canvas.width + maxShapeSize ||
            shape.y < -maxShapeSize ||
            shape.y > canvas.height + maxShapeSize
          ) {
            shapes.splice(i, 1); // Usunięcie kształtu, który wyszedł poza canvas
            i--;
            continue;
          }

          // Odbijanie się od innych kształtów
          for (var j = 0; j < shapes.length; j++) {
            if (i !== j) {
              var otherShape = shapes[j];
              var dx = otherShape.x - shape.x;
              var dy = otherShape.y - shape.y;
              var distance = Math.sqrt(dx * dx + dy * dy);
              var minDistance = (shape.size + otherShape.size) / 2;

              if (distance < minDistance) {
                var angle = Math.atan2(dy, dx);
                var targetX =
                  shape.x - (Math.cos(angle) * (minDistance - distance)) / 2;
                var targetY =
                  shape.y - (Math.sin(angle) * (minDistance - distance)) / 2;

                shape.vx += (shape.x - targetX) * 0.01;
                shape.vy += (shape.y - targetY) * 0.01;
              }
            }
          }

          // Sprawdzanie kolizji z białą kulą
          if (
            Math.abs(shape.x - cursorBall.x) <
              shape.size / 2 + cursorBall.radius &&
            Math.abs(shape.y - cursorBall.y) <
              shape.size / 2 + cursorBall.radius
          ) {
            // Resetowanie gry
            sendScoreToPHP(score, nickname);
            enemySpawnTime = 1000;
            shapes = [];
            score = 0;
            updateScoreDisplay();
            break;
          }

          drawShape(shape);
        }

        drawCursorBall();

        drawTrail();
      }

      // Zwiększanie wyniku gry co określony czas
      setInterval(function () {
        clearInterval(enemySpawnInterval);
        startEnemySpawnInterval();
        increaseScore();
        console.log(enemySpawnTime);
      }, scoreUpdateTime);

      // Obsługa ruchu białej kulki za kursorem
      canvas.addEventListener("mousemove", function (event) {
        cursorBall.x = event.clientX - canvas.offsetLeft;
        cursorBall.y = event.clientY - canvas.offsetTop;
      });

      // Pętla główna
      function gameLoop() {
        update();
        requestAnimationFrame(gameLoop);
      }

      document.addEventListener("visibilitychange", function () {
        if (document.visibilityState === "hidden") {
          pauseGame();
        } else if (document.visibilityState === "visible") {
          resumeGame();
        }
      });

      // Uruchomienie pętli głównej
      gameLoop();
      nickChange();
    </script>
  </body>
</html>
