import express from "express";
import cors from "cors";
import 'dotenv/config';
import fetch from "node-fetch";

const app = express();

// CORS для фронтенда на XAMPP (укажи порт фронтенда, например 80 или 8080)
app.use(cors({
  origin: "http://localhost",  // замените на http://localhost:8080 если XAMPP на 8080
  methods: ["GET", "POST", "OPTIONS"],
  allowedHeaders: ["Content-Type", "Authorization"]
}));

// Обработка preflight-запросов
app.options("*", cors());

app.use(express.json());

// ==== Проверка работы сервера ====
app.get("/", (req, res) => {
  console.log("GET / получен");
  res.send("Server работает!");
});

// ==== Маршрут чата ====
app.post("/chat", async (req, res) => {
  console.log("REQ BODY:", req.body);  // тело запроса от фронтенда

  try {
    const response = await fetch("https://openrouter.ai/api/v1/chat/completions", {
      method: "POST",
      headers: {
        "Authorization": `Bearer ${process.env.OPENROUTER_API_KEY}`,
        "Content-Type": "application/json"
      },
      body: JSON.stringify(req.body)
    });

    console.log("RESPONSE STATUS:", response.status);

    const data = await response.json();
    console.log("API RESPONSE:", data);

    res.json(data);
  } catch (err) {
    console.error("SERVER ERROR:", err);
    res.status(500).json({ error: "Server error", details: err.message });
  }
});

// ==== Запуск сервера ====
const PORT = 3000;
app.listen(PORT, () => console.log(`Server działa na http://localhost:${PORT}`));