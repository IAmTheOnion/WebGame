<?php 
    $conn = new mysqli("db4free.net", "root", "yQtu5uX*#yj-kN*", "statystyki");
    // Pobranie wyniku i nicka z parametrów POST
    $score = $_POST['score'];
    $nick = $_POST['nick'];

    // Pobranie najniższego wyniku z tabeli
    $sql = "SELECT MIN(wynik) AS min_wynik FROM statystyki";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $minScore = $row["min_wynik"];

    // Porównanie aktualnego wyniku z najniższym wynikiem
    if ($score> $minScore) {
        // Usunięcie rekordu z najniższym wynikiem
        $deleteSql = "DELETE FROM statystyki WHERE wynik = $minScore LIMIT 1";
        $conn->query($deleteSql);

        // Dodanie nowego rekordu z aktualnym wynikiem
        $sql = "INSERT INTO statystyki (wynik, nazwa) VALUES ('$score', '$nick')";
        $conn->query($sql);
    } 
    // Zamknięcie połączenia z bazą danych
    $conn->close();
?>