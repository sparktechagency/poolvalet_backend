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

let users = {};
const groups = {};

io.on("connection", (socket) => {
  console.log(`User Connected: ${socket.id}`);

  const userId = socket.handshake.query.userId;

  users[userId] = socket.id;
  console.log("socket is", socket.id, "user id", userId);
  console.log(users);

  // socket.on('chat',function(msg){
  //     io.sockets.emit('chat',msg);
  // });

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

  socket.on("disconnect", () => {
    for (let userId in users) {
      if (users[userId] === socket.id) {
        delete users[userId];
        break;
      }
    }
    console.log("User Disconnected:", socket.id);
    console.log("Users List:", users);
  });
});

server.listen(3000, () => {
  console.log("Server running on http://10.10.10.65:3000");
});
