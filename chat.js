import express from "express";
import { Server } from "socket.io";
import http from "http";
import cors from "cors";

const app = express();
const server = http.createServer(app);
const io = new Server(server, {
    cors: {
        origin: "*"
    }
});

app.use(cors());
app.use(express.static("public"));

let users = {}; // userId -> socketId
let onlineUsers = new Set(); // শুধু userId রাখবে

io.on("connection", (socket) => {
    console.log(`User Connected: ${socket.id}`);

    const userId = socket.handshake.query.userId;
    users[userId] = socket.id;
    socket.userId = userId; // disconnect এ কাজ হবে

    // ✅ online list এ add করলাম
    onlineUsers.add(userId);
    io.emit("updateUsers", Array.from(onlineUsers));

    console.log("socket is", socket.id, "user id", userId);
    console.log("Users Map:", users);
    console.log("Online Users:", Array.from(onlineUsers));

    // 🔹 Private Message Handle
    socket.on("privateMessage", ({ receiverId, message }) => {
        console.log(
            `Sending private message from ${userId} to ${receiverId} : ${message}`
        );

        const senderSocketId = socket.id;
        const receiverSocketId = users[receiverId];

        // Send message to sender (confirmation or for UI update)
        io.to(senderSocketId).emit("privateMessage", {
            receiver_id: receiverId,
            sender_id: userId,
            message,
        });

        // Send message to receiver if online
        if (receiverSocketId) {
            io.to(receiverSocketId).emit("privateMessage", {
                receiver_id: receiverId,
                sender_id: userId,
                message,
            });
        } else {
            console.log(`User ${receiverId} not found or offline`);
        }
    });

    // 🔹 Disconnect Handle
    socket.on("disconnect", () => {
        if (socket.userId) {
            delete users[socket.userId]; // socketId মুছে ফেলবো
            onlineUsers.delete(socket.userId); // online থেকে বাদ দিবো
            io.emit("updateUsers", Array.from(onlineUsers));
        }

        console.log("User Disconnected:", socket.id);
        console.log("Users Map:", users);
        console.log("Online Users:", Array.from(onlineUsers));
    });
});

server.listen(3000, () => {
    console.log("Server running on http://10.10.10.65:3000");
});
