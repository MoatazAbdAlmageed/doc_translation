# TransRead AI - Dual Language English & Arabic Reader

TransRead AI is a web-based application designed specifically for Arabic speakers who are learning English. It allows users to upload various document formats and read them with a human-like, paragraph-by-paragraph Arabic translation, complete with interactive learning tools.

## Features

- **Multi-Format Document Support**: Upload `.txt`, `.doc`, `.docx`, `.epub`, `.pdf`, and `.md` files.
- **Dual Translation Engines**:
  - **Standard**: Uses a fast, free Google Translate proxy.
  - **Ultra AI**: Uses the Google Gemini API for highly accurate, human-like, context-aware translation. It includes a dynamic model negotiator to automatically switch to the best available Gemini model.
- **Interactive Reading Workspace**:
  - **Customizable Layout**: Choose between a Side-by-Side (English/Arabic) or Stacked layout.
  - **Click-to-Define Dictionary**: Click any English word while reading to instantly view its phonetic pronunciation, part of speech, English definition, and Arabic translation.
  - **Native Text-to-Speech (TTS)**: Click the audio button next to any paragraph to hear the correct native pronunciation of the English text.
- **Asynchronous Processing**: Translations are queued and processed asynchronously in the background for a smooth reading experience.
- **Local Caching**: Translations are cached in an SQLite database, ensuring lightning-fast load times on subsequent reads and zero extra API consumption.
- **In-App API Key Testing**: Instantly verify your Gemini API key in the settings panel with a custom test paragraph before reading.

## Technology Stack

- **Backend**: PHP (7.4+)
- **Database**: SQLite (via PDO)
- **Frontend**: Vanilla JavaScript (ES6+), HTML5, CSS3 (Custom Glassmorphism UI)
- **External APIs**:
  - Google Gemini API (for advanced AI translation)
  - Unofficial Google Translate API (for standard translation and word lookup)
  - Free Dictionary API (`dictionaryapi.dev` for word definitions)

## Requirements

- A web server running PHP (e.g., XAMPP, WAMP, or Nginx/Apache).
- PHP Extensions enabled:
  - `pdo_sqlite`
  - `curl` (for external API requests)
  - `fileinfo` (for MIME type checking during upload)

## Installation & Setup

1. **Clone or Extract the Repository**:
   Place the project files into your web server's root directory (e.g., `C:\xampp\htdocs\doc_translation`).

2. **Directory Permissions**:
   Ensure the web server has write permissions to the root folder. The application will automatically create an `uploads/` directory and a `database.sqlite` file on its first run.

3. **Get a Gemini API Key (Optional but Recommended)**:
   To unlock the human-like Ultra AI translation, obtain a free API key from [Google AI Studio](https://aistudio.google.com/).

4. **Launch**:
   Open your browser and navigate to the application URL (e.g., `http://localhost/doc_translation/index.php`).

## Usage Guide

1. **Upload a Document**: On the main dashboard, drag and drop your supported file into the upload zone, or click to browse. Wait for the upload and parsing to complete.
2. **Configure Translation**: On the right sidebar, choose your preferred translation engine. If using "Ultra AI," enter your Gemini API Key. You can use the "Test API Key" tool directly below it to ensure it works.
3. **Start Reading**: Click the "Read" button next to your uploaded document in "My Library".
4. **Learn Interactively**: 
   - Click on any word you don't know to see its definition and translation.
   - Click the speaker icon to listen to the paragraph.
   - Your translations will process automatically as you read.

## License

This project is open-source and free to use for personal and educational purposes.
