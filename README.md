# Twitch Stream Category Tracker

## English

A Python script that periodically collects Twitch streamers’ live categories and saves them to a MySQL database. Includes a modern, dark-themed web dashboard (HTML, CSS, JS, PHP) for analyzing and visualizing the data. The website supports both Polish and English, allows you to select streamers, set time ranges, view category stats, and see charts of streamed categories. Basic streamer info is fetched from TwitchTracker.

Features:

Automatic data collection from Twitch API (only when streamers are online)

Responsive, modern web dashboard (PL/EN)

Category/time stats, sorting, and chart visualization

## Polski

Skrypt w Pythonie, który cyklicznie pobiera dane o kategoriach streamów wybranych streamerów Twitch i zapisuje je do bazy MySQL. W repozytorium znajduje się również nowoczesna, ciemna strona internetowa (HTML, CSS, JS, PHP) do analizy i wizualizacji danych. Strona obsługuje język polski i angielski, umożliwia wybór streamera, zakresu czasu, przeglądanie statystyk kategorii oraz wykresów. Podstawowe dane o streamerze pobierane są z TwitchTracker.

Funkcje:

Automatyczne zbieranie danych z API Twitcha (tylko gdy streamer jest online)

Nowoczesny, responsywny panel webowy (PL/EN)

Statystyki kategorii/czasu, sortowanie, wykresy

## Installation
Install Python dependencies:

> pip install requests mysql-connector-python

Configure your database and Twitch API credentials in the Python script.

Run the Python collector script.

Deploy the web files on your PHP server.

Open the website in your browser.

© xFilioo 2025
