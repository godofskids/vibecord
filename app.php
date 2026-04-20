<?php
require "config/db.php";
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Vibecord</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="layout">

  <!-- LEFT BAR -->
  <div class="servers">
    <div class="server" onclick="backToDMs()" title="Direct Messages">
      <img src="default.png" alt="DMs" />
    </div>
    <div id="servers"></div>
  </div>

  <!-- SIDEBAR -->
  <div class="sidebar">
    <div class="sidebar-tabs">
      <select id="viewSelect" onchange="handleViewChange()">
        <option value="dms">Messages</option>
        <option value="friends">Friends</option>
        <option value="pending">Pending</option>
        <option value="blocked">Blocked</option>
      </select>
      <button id="createFriendBtn" onclick="addFriend()" title="Add Friend">+</button>
    </div>
    <div id="channelList" class="channel-list"></div>
  </div>

  <!-- CHAT AREA -->
  <div class="chat" id="chatArea" style="display:none;">
    <div class="chat-main">
      <div class="server-header-bar" id="serverHeaderBar" style="display:none;">
        <span id="serverHeaderName"></span>
      </div>
      <div class="chat-header" id="chatTitle">
        Select a friend
        <span class="call-actions" style="display:none;margin-left:auto;">
          <button onclick="startVoiceCall()" title="Voice Call" style="padding:4px 8px;font-size:14px;">📞</button>
          <button onclick="startVideoCall()" title="Video Call" style="padding:4px 8px;font-size:14px;">📹</button>
        </span>
        <span class="group-header-actions" style="display:none;margin-left:auto;">
          <button onclick="startGroupVoiceCall()" title="Voice Call" style="padding:4px 8px;font-size:14px;">📞</button>
          <button onclick="showGroupMembersPanel()" style="padding:4px 8px;font-size:12px;">Members</button>
          <button onclick="showGroupInviteModal()" style="padding:4px 8px;font-size:12px;">+ Invite</button>
          <button onclick="leaveCurrentGroup()" style="padding:4px 8px;font-size:12px;background:#e74c3c;">Leave</button>
          <button onclick="showRenameGroupModal()" style="padding:4px 8px;font-size:12px;">✏️ Rename</button>
        </span>
      </div>
      <div id="messages" class="messages"></div>
      <div id="typingIndicator" class="typing-indicator"></div>
      <div class="chat-input">
        <input id="msg" placeholder="Message..." onkeydown="if(event.key==='Enter') sendMessage()" oninput="handleTyping()">
        <button onclick="sendMessage()">Send</button>
      </div>
    </div>
    <!-- Right sidebar for friend profile -->
    <div class="chat-sidebar" id="chatSidebar" style="display:none;">
      <div class="profile-section">
        <img id="chatFriendPfp" class="profile-avatar" src="">
        <span id="chatFriendName" class="profile-name"></span>
        <!--TODO: User status-->
        <!--<span id="chatFriendStatus" class="profile-status">Online</span>-->
      </div>
      <div class="profile-actions">
        <button onclick="blockFromChat()">Block</button>
        <button onclick="unfriendFromChat()">Unfriend</button>
      </div>
    </div>
    <!-- Right sidebar for group members -->
    <div class="chat-sidebar" id="groupSidebar" style="display:none;">
      <div class="profile-section">
        <span class="profile-name">Group Members</span>
      </div>
      <div id="groupMembersList" class="group-members-list"></div>
      <div class="profile-actions">
        <button onclick="showGroupMembersPanel()">View All</button>
        <button onclick="showGroupInviteModal()">+ Invite</button>
        <button onclick="leaveCurrentGroup()" style="background:#e74c3c;">Leave</button>
      </div>
    </div>
  </div>

</div>

<!-- FRIEND REQUEST POPUP -->
<div id="friendRequestPopup" class="popup" style="display:none;">
  <div class="popup-content">
    <h3>New Friend Request!</h3>
    <p id="popupUsername"></p>
    <div class="popup-actions">
      <button onclick="acceptFriendFromPopup()">Accept</button>
      <button onclick="declineFriendFromPopup()">Decline</button>
    </div>
  </div>
</div>

<!-- SERVER SETTINGS PANEL -->
<div id="serverSettings" class="server-settings">
  <span class="server-settings-close" onclick="closeServerSettings()">✕</span>
  <h3>Server Settings</h3>
  <button onclick="showInviteForm()" style="width:100%; margin-bottom:10px;">+ Invite Member</button>
  <div id="inviteForm" style="display:none; margin-bottom:10px;">
    <input id="inviteUsername" placeholder="Username to invite" style="width:100%; padding:5px; margin-bottom:5px;">
    <button onclick="inviteMember()" style="width:100%;">Invite</button>
  </div>
  <div id="serverMembersList"></div>
  <hr style="border-color:#202225; margin:15px 0;">
  <h4>Roles</h4>
  <div id="serverRolesList"></div>
  <button onclick="showCreateRoleForm()" style="margin-top:10px; width:100%;">+ Create Role</button>
  <div id="createRoleForm" style="display:none; margin-top:10px;">
    <input id="newRoleName" placeholder="Role name" style="width:100%; padding:5px; margin-bottom:5px;">
    <input id="newRoleColor" type="color" value="#99AAB5" style="width:100%; margin-bottom:5px;">
    <button onclick="createRole()" style="width:100%;">Create</button>
  </div>
</div>

<!-- USER BAR -->
<div class="user-bar">
  <img id="userPfp" src="default.png">
  <span id="usernameDisplay">
    Username
  </span>
  <form method="POST" action="api/logout.php">
    <button id="logoutDisplay" type="submit">Logout</button>
  </form>
</div>

<script>
let currentFriend = null;
let currentTab = 'dms';
let typingTimeout = null;
let isTyping = false;
let currentView = 'dms'; // 'dms', 'server', or 'group'
let currentServerId = null;
let currentUserId = null;
let currentGroupId = null;
let currentGroupName = null;

// Get current user ID on load
fetch("api/get_me.php")
  .then(r=>r.json())
  .then(d=>{
    if(d.user){
      currentUserId = d.user.id;
    }
  });

// --- Check for notifications (friend requests, group invites) ---
function checkNotifications(){
  fetch("api/get_friends.php")
    .then(r=>r.json())
    .then(d=>{
      // Update pending badge
      const pendingCount = (d.pending ? d.pending.length : 0) + (d.sent ? d.sent.length : 0);
      const pendingBadge = document.getElementById("pendingBadge");
      if(pendingBadge){
        if(pendingCount > 0){
          pendingBadge.style.display = 'inline';
          pendingBadge.innerText = pendingCount;
        } else {
          pendingBadge.style.display = 'none';
        }
      }
      
      // Update messages badge (new messages - would need actual implementation)
      // For now just show group invites count
      fetch("api/get_groups.php")
        .then(g=>g.json())
        .then(gData=>{
          const inviteCount = gData.invitations ? gData.invitations.length : 0;
          const msgBadge = document.getElementById("msgBadge");
          if(msgBadge){
            if(inviteCount > 0){
              msgBadge.style.display = 'inline';
              msgBadge.innerText = inviteCount;
            } else {
              msgBadge.style.display = 'none';
            }
          }
        });
    });
}

// Check notifications on load and every 30 seconds
setInterval(checkNotifications, 30000);
checkNotifications();

// --- Load and display friends / requests / groups ---
function showFriendsTab(tab){
  currentTab = tab;
  currentView = 'dms';
  currentServerId = null;

  // Show friend tabs
  document.querySelector('.sidebar-tabs').classList.remove('hidden');

  // Sync dropdown with current tab
  document.getElementById("viewSelect").value = tab;

  fetch("api/get_friends.php")
    .then(r=>r.json())
    .then(data=>{
      let html = "";

      if(tab === 'dms'){
        // Show both friends and groups in Messages tab
        let dmsHtml = `<div class="channel" style="justify-content:space-between;display:flex;">
                  <span>Messages</span>
                  <button id="createGroupBtn" onclick="showCreateGroupModal()" title="+ Create Group">+ Create Group</button>
                </div>`;
        
        // Show friends
        if(data.friends.length>0){
          data.friends.forEach(f=>{
            dmsHtml += `<div class="channel" onclick="selectFriend(${f.id},'${f.username}')">👤 ${f.username}</div>`;
          });
        }
        
        // Show groups
        fetch("api/get_groups.php")
          .then(r=>r.json())
          .then(gData=>{
            if(gData.success && gData.groups && gData.groups.length>0){
              gData.groups.forEach(g=>{
                dmsHtml += `<div class="channel" onclick="openGroupChat(${g.id},'${g.name}')">📁 ${g.name}</div>`;
              });
            }
            // Show pending group invitations
            if(gData.invitations && gData.invitations.length>0){
              dmsHtml += `<div style="padding:8px;color:#f1c40f;font-weight:bold;">Group Invitations:</div>`;
              gData.invitations.forEach(inv=>{
                dmsHtml += `<div class="channel">
                  <span>📥 ${inv.group_name}</span>
                  <span class="friend-actions">
                    <button onclick="acceptGroupInvite(${inv.id})">Accept</button>
                    <button onclick="declineGroupInvite(${inv.id})">Decline</button>
                  </span>
                </div>`;
              });
            }
            if(data.friends.length===0 && (!gData.groups || gData.groups.length===0)){
              dmsHtml += "<i>No messages yet. Add friends or create a group!</i>";
            }
            document.getElementById("channelList").innerHTML = dmsHtml;
          });
        return; // Return early since we're doing async
      } 
      else if(tab === 'friends'){
        if(data.friends.length>0){
          data.friends.forEach(f=>{
            html += `<div class="channel">
                      ${f.username}
                      <span class="friend-actions">
                        <button onclick="unfriendUser(${f.id})">Unfriend</button>
                        <button onclick="blockUser(${f.id})">Block</button>
                      </span>
                    </div>`;
          });
        } else html = "<i>No friends yet.</i>";
      } 
      else if(tab === 'pending'){
        // Combine received requests and sent requests
        let hasPending = false;
        let pendingHtml = "";
        
        // Show received requests
        if(data.pending && data.pending.length>0){
          data.pending.forEach(p=>{
            pendingHtml += `<div class="channel">
                      ${p.username} (Request)
                      <span class="friend-actions">
                        <button onclick="acceptFriend(${p.id})">Accept</button>
                        <button onclick="declineFriend(${p.id})">Decline</button>
                      </span>
                    </div>`;
            hasPending = true;
          });
        }
        
        // Show sent requests
        if(data.sent && data.sent.length>0){
          data.sent.forEach(s=>{
            pendingHtml += `<div class="channel">
                      ${s.username} (Sent)
                      <span class="friend-actions">
                        <button onclick="cancelFriendRequest(${s.id})">Cancel</button>
                      </span>
                    </div>`;
            hasPending = true;
          });
        }
        
        if(hasPending){
          html = pendingHtml;
        } else {
          html = "<i>No pending requests.</i>";
        }
      }
      else if(tab === 'blocked'){
        if(data.blocked && data.blocked.length>0){
          data.blocked.forEach(b=>{
            html += `<div class="channel">
                      ${b.username} 
                      <span class="friend-actions">
                        <button onclick="unblockUser(${b.id})">Unblock</button>
                      </span>
                    </div>`;
          });
        } else html = "<i>No blocked users.</i>";
      }
      document.getElementById("channelList").innerHTML = html;
    });
}

// --- Handle dropdown view change ---
function handleViewChange(){
  const selectedView = document.getElementById("viewSelect").value;
  showFriendsTab(selectedView);
}

// --- Cancel friend request ---
function cancelFriendRequest(id){
  if(!confirm("Cancel this friend request?")) return;
  fetch("api/cancel_friend_request.php",{
    method:"POST",
    body:new URLSearchParams({target_id:id})
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.success){
      showFriendsTab(currentTab);
    } else {
      alert(d.error);
    }
  });
}

// --- Add friend ---
function addFriend(){
  let username = prompt("Enter username to add:");
  if(!username) return;
  fetch("api/send_friend_request.php",{
    method:"POST",
    body:new URLSearchParams({username})
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.success) alert("Friend request sent!");
    else alert(d.error);
    showFriendsTab(currentTab);
  });
}

// --- Accept / Decline friend ---
function acceptFriend(id){
  fetch("api/accept_friend_request.php",{method:"POST", body:new URLSearchParams({friend_id:id})})
    .then(()=>showFriendsTab(currentTab));
}
function declineFriend(id){
  fetch("api/decline_friend_request.php",{method:"POST", body:new URLSearchParams({friend_id:id})})
    .then(()=>showFriendsTab(currentTab));
}

// --- Unfriend / Block user ---
function unfriendUser(id){
  if(!confirm("Unfriend this user?")) return;
  fetch("api/unfriend.php",{
    method:"POST",
    body:new URLSearchParams({friend_id:id})
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.success){
      showFriendsTab(currentTab);
    } else {
      alert(d.error);
    }
  });
}

function blockUser(id){
  if(!confirm("Block this user?")) return;
  fetch("api/block_user.php",{
    method:"POST",
    body:new URLSearchParams({block_id:id})
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.success){
      showFriendsTab(currentTab);
    } else {
      alert(d.error);
    }
  });
}

function unblockUser(id){
  fetch("api/unblock_user.php",{
    method:"POST",
    body:new URLSearchParams({block_id:id})
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.success){
      showFriendsTab(currentTab);
    } else {
      alert(d.error);
    }
  });
}

// --- Group Chat Functions ---

function showCreateGroupModal(){
  const name = prompt("Enter group name:");
  if(!name) return;
  const maxMembers = prompt("Max members (default 10):", "10");
  
  fetch("api/create_group_chat.php",{
    method:"POST",
    body:new URLSearchParams({name:name, max_members:maxMembers||10})
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.success){
      if(d.warning) alert(d.warning);
      showFriendsTab('dms');
    } else {
      alert(d.error);
    }
  });
}

function openGroupChat(groupId, groupName){
  currentGroupId = groupId;
  currentGroupName = groupName; // Store the group name
  currentView = 'group';
  currentFriend = null;
  currentServerId = null;
  
  console.log("Opening group chat:", groupId, groupName, "currentView:", currentView);
  
  // Hide friend sidebar, show group sidebar, clear messages
  const chatSidebar = document.getElementById("chatSidebar");
  const groupSidebar = document.getElementById("groupSidebar");
  const messagesEl = document.getElementById("messages");
  const chatTitleEl = document.getElementById("chatTitle");
  const chatAreaEl = document.getElementById("chatArea");
  
  if(chatSidebar) chatSidebar.style.display = 'none';
  if(groupSidebar) groupSidebar.style.display = 'flex';
  if(messagesEl) messagesEl.innerHTML = '';
  if(chatTitleEl) chatTitleEl.innerHTML = "📁 " + groupName;
  
  // Show group header actions, hide call actions
  const groupHeaderEl = chatTitleEl ? chatTitleEl.querySelector('.group-header-actions') : null;
  const callActionsEl = chatTitleEl ? chatTitleEl.querySelector('.call-actions') : null;
  if(groupHeaderEl) groupHeaderEl.style.display = 'inline-flex';
  if(callActionsEl) callActionsEl.style.display = 'none';
  if(chatAreaEl) chatAreaEl.style.display = 'flex';
  
  // Use the correct messages div - for groups we use messages
  const messagesContainer = document.getElementById("messages");
  
  // Check if we should show members panel (restore state)
  const savedState = localStorage.getItem('groupMembersPanelOpen') === 'true';
  if(savedState){
    showGroupMembersPanel();
    return;
  }
  
  // Load group messages and members
  fetch("api/get_group_messages.php",{
    method:"POST",
    body:new URLSearchParams({group_id:groupId})
  })
  .then(r=>r.json())
  .then(d=>{
    console.log("Group messages response:", d);
    if(d.success){
      renderGroupMessages(d.messages, messagesContainer);
      renderGroupMembers(d.members);
    } else {
      messagesContainer.innerHTML = "<i>" + (d.error || "Error loading messages") + "</i>";
    }
  })
  .catch(err=>{
    console.error("Error loading group:", err);
    messagesContainer.innerHTML = "<i>Error loading messages</i>";
  });
}

function renderGroupMessages(messages, container){
  if(!container) container = document.getElementById("messages");
  let html = "";
  
  messages.forEach(m=>{
    const time = new Date(m.created_at).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
    const isMine = m.sender_id == currentUserId;
    let initial = m.sender_name ? m.sender_name[0].toUpperCase() : '?';
    
    // Use same format as friend chat
    html += `<div class="message" data-msg-id="${m.id}">
      <div class="message-avatar">${initial}</div>
      <div class="message-content">
        <div class="message-header">
          <span class="message-username">${m.sender_name}</span>
          <span class="message-time">${time}</span>
        </div>
        <div class="message-text">${escapeHtml(m.message)}</div>
      </div>
      <div class="msg-actions">
        <button class="react-btn" onclick="showReactionPicker(${m.id})">😀</button>
        ${isMine ? '<button class="delete-msg-btn" onclick="deleteGroupMessage(' + m.id + ')">🗑️</button>' : ''}
      </div>
      <div class="msg-reactions" id="reactions-${m.id}"></div>
    </div>`;
  });
  container.innerHTML = html;
  container.scrollTop = container.scrollHeight;
  
  // Load reactions for all messages
  if(messages.length > 0){
    const msgIds = messages.map(m=>m.id).join(',');
    fetch("api/get_reactions.php",{
      method:"POST",
      body:new URLSearchParams({message_ids:msgIds, message_type:'group'})
    })
    .then(r=>r.json())
    .then(d=>{
      if(d.success && d.reactions){
        Object.keys(d.reactions).forEach(msgId=>{
          renderMessageReactions(msgId, d.reactions[msgId]);
        });
      }
    });
  }
}

function renderMessageReactions(msgId, reactions){
  const container = document.getElementById(`reactions-${msgId}`);
  if(!container || !reactions || reactions.length === 0) return;
  
  // Group by emoji
  const grouped = {};
  reactions.forEach(r=>{
    if(!grouped[r.emoji]) grouped[r.emoji] = [];
    grouped[r.emoji].push(r.username);
  });
  
  let html = '<div class="reaction-list">';
  Object.keys(grouped).forEach(emoji=>{
    html += `<span class="reaction" onclick="toggleReaction(${msgId},'${emoji}')">${emoji} ${grouped[emoji].length}</span>`;
  });
  html += '</div>';
  container.innerHTML = html;
}

function showReactionPicker(msgId){
  // Close any existing picker first
  closeReactionPicker();
  
  const emojis = ['👍','❤️','😂','😮','😢','🙏','🎉','🔥'];
  let html = '<div class="reaction-picker" id="activeReactionPicker">';
  html += '<button class="close-picker" onclick="closeReactionPicker()">✕</button>';
  emojis.forEach(e=>{
    html += `<button onclick="toggleReaction(${msgId},'${e}')">${e}</button>`;
  });
  html += '</div>';
  
  // Show picker near the message
  const msgEl = document.querySelector(`[data-msg-id="${msgId}"]`);
  if(msgEl){
    msgEl.innerHTML += html;
  }
}

function closeReactionPicker(){
  const picker = document.getElementById("activeReactionPicker");
  if(picker){
    picker.remove();
  }
}

// Close picker when clicking outside
document.addEventListener('click', function(e){
  if(!e.target.closest('.reaction-picker') && !e.target.closest('.react-btn')){
    closeReactionPicker();
  }
});

function toggleReaction(msgId, emoji){
  // Determine message type based on current view
  let messageType = 'group';
  if(currentView === 'dms') messageType = 'dm';
  
  fetch("api/react_message.php",{
    method:"POST",
    body:new URLSearchParams({message_id:msgId, emoji:emoji, message_type:messageType})
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.success){
      // Reload reactions
      fetch("api/get_reactions.php",{
        method:"POST",
        body:new URLSearchParams({message_ids:msgId, message_type:messageType})
      })
      .then(r=>r.json())
      .then(d=>{
        if(d.success && d.reactions && d.reactions[msgId]){
          renderMessageReactions(msgId, d.reactions[msgId]);
        } else {
          const el = document.getElementById("reactions-" + msgId);
          if(el) el.innerHTML = '';
        }
      });
    }
  });
}

function deleteGroupMessage(msgId){
  if(!confirm("Delete this message?")) return;
  fetch("api/delete_group_message.php",{
    method:"POST",
    body:new URLSearchParams({message_id:msgId})
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.success){
      openGroupChat(currentGroupId, currentGroupName);
    } else {
      alert(d.error);
    }
  });
}

function renderGroupMembers(members){
  // Show members in the group sidebar
  const membersList = document.getElementById("groupMembersList");
  if(!membersList) return;
  
  let html = '';
  if(members && members.length > 0){
    members.forEach(m=>{
      let initial = m.username ? m.username[0].toUpperCase() : '?';

      let role = ''
      if (m.role === 'admin') {
        role = '👑'
      }
      else {
        role = ''//m.role
      }
      
      html += '<div class="group-member-item">' +
        '<img src="https://ui-avatars.com/api/?name=' + encodeURIComponent(initial) + '&background=6366f1&color=fff&size=32" class="member-avatar">' +
        '<span class="member-name">' + m.username + '</span>' +
        '<span class="member-role">' + role + '</span>' +
        '</div>';
    });
  } else {
    html = '<p>No members</p>';
  }
  membersList.innerHTML = html;
}

function sendGroupMessage(){
  const input = document.getElementById("chatInput");
  const message = input.value.trim();
  if(!message || !currentGroupId) return;
  
  fetch("api/send_group_message.php",{
    method:"POST",
    body:new URLSearchParams({group_id:currentGroupId, message:message})
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.success){
      input.value = "";
      // Reload messages
      openGroupChat(currentGroupId, currentGroupName);
    } else {
      alert(d.error);
    }
  });
}

function acceptGroupInvite(inviteId){
  fetch("api/accept_group_invite.php",{
    method:"POST",
    body:new URLSearchParams({invite_id:inviteId})
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.success){
      showFriendsTab('dms');
    } else {
      alert(d.error);
    }
  });
}

function declineGroupInvite(inviteId){
  fetch("api/decline_group_invite.php",{
    method:"POST",
    body:new URLSearchParams({invite_id:inviteId})
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.success){
      showFriendsTab('dms');
    } else {
      alert(d.error);
    }
  });
}

function escapeHtml(text){
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// --- Group Management ---
let groupMembersPanelOpen = false;

function showGroupMembersPanel(){
  if(!currentGroupId) return;
  groupMembersPanelOpen = true;
  localStorage.setItem('groupMembersPanelOpen', 'true');
  
  console.log("Showing group members panel for group:", currentGroupId);
  
  fetch("api/get_group_messages.php",{
    method:"POST",
    body:new URLSearchParams({group_id:currentGroupId})
  })
  .then(r=>r.json())
  .then(d=>{
    console.log("Members response:", d);
    if(d.success){
      let html = '<div class="group-members-panel">';
      html += '<button id="backToChatBtn" onclick="backToGroupChat()" title="Back to Chat">← Back to Chat</button>';
      html += '<h3>Group Members</h3>';
      if (d.members && d.members.length > 0) {
        // Find current user's role
        const currentUserMember = d.members.find(m => m.user_id == currentUserId);
        const isCurrentUserAdmin = currentUserMember && currentUserMember.role === 'admin';
        
        d.members.forEach(m => {
          let transferBtn = '';
          let role = '';

          if (m.role === 'admin') {
            role = '👑';
          }

          // Only show transfer button if current user is admin AND the other user is NOT an admin
          if (isCurrentUserAdmin && m.role !== 'admin') {
            transferBtn = '<button onclick="giveGroupOwnership(' + m.user_id + ')">Transfer Ownership</button>';
          }

          html += '<div class="group-member">' +
            '<span>' + m.username + role + '</span>' +
            transferBtn +
            '</div>';
        });
      } else {
        html += '<p>No members found</p>';
      }
      html += '<div class="group-actions">';
      html += '<button onclick="showGroupInviteModal()">+ Invite User</button>';
      html += '<button onclick="leaveCurrentGroup()" style="background:#e74c3c;">Leave Group</button>';
      html += '<button onclick="showRenameGroupModal()">✏️ Rename</button>';
      html += '</div></div>';
      
      // Show in the messages div
      document.getElementById("messages").innerHTML = html;
    } else {
      alert(d.error);
    }
  });
}

function hideGroupMembersPanel(){
  groupMembersPanelOpen = false;
  localStorage.setItem('groupMembersPanelOpen', 'false');

  console.log("Hiding group members panel");

  // restore the normal chat UI
  if (currentGroupId) {
    loadGroupMessages(currentGroupId); // assumes you already have this function
  } else {
    document.getElementById("messages").innerHTML = "<p>No group selected</p>";
  }
}

function backToGroupChat(){
  groupMembersPanelOpen = false;
  localStorage.setItem('groupMembersPanelOpen', 'false');
  if(currentGroupId && currentGroupName){
    openGroupChat(currentGroupId, currentGroupName);
  }
}

function showGroupInviteModal(){
  // Show a modal with friends list to select
  fetch("api/get_friends.php")
    .then(r=>r.json())
    .then(data=>{
      if(!data.friends || data.friends.length === 0){
        alert("No friends to invite. Add friends first!");
        return;
      }
      
      let html = '<div id="groupInviteModal" class="popup" style="display:block;">';
      html += '<div class="popup-content">';
      html += '<h3>Invite Friend to Group</h3>';
      html += '<div class="channel-list" style="max-height:300px;overflow-y:auto;">';
      
      data.friends.forEach(f=>{
        html += `<div class="channel" onclick="inviteFriendToGroup(${f.id}, '${f.username}')">
          👤 ${f.username}
        </div>`;
      });
      
      html += '</div>';
      html += '<button id="cancelBtn" onclick="closeGroupInviteModal()" title="Cancel">Cancel</button>';
      html += '</div></div>';
      
      // Remove existing modal if any
      const existing = document.getElementById("groupInviteModal");
      if(existing) existing.remove();
      
      document.body.insertAdjacentHTML('beforeend', html);
    });
}

function closeGroupInviteModal(){
  const modal = document.getElementById("groupInviteModal");
  if(modal) modal.remove();
}

function inviteFriendToGroup(userId, username){
  if(!confirm("Invite " + username + " to this group?")) return;
  
  fetch("api/invite_to_group.php",{
    method:"POST",
    body:new URLSearchParams({group_id:currentGroupId, user_id:userId})
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.success){
      alert("Invitation sent to " + username + "!");
      closeGroupInviteModal();
    } else {
      alert(d.error);
    }
  });
}

function showRenameGroupModal(){
  const newName = prompt("Enter new group name:", currentGroupName);
  if(!newName || newName === currentGroupName) return;
  
  fetch("api/rename_group.php",{
    method:"POST",
    body:new URLSearchParams({group_id:currentGroupId, name:newName})
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.success){
      currentGroupName = newName;
      document.getElementById("chatTitle").innerHTML = "📁 " + newName;
      // Refresh members panel if open
      if(groupMembersPanelOpen){
        showGroupMembersPanel();
      }
    } else {
      alert(d.error);
    }
  });
}

function leaveCurrentGroup(){
  if(!confirm("Leave this group?")) return;
  
  fetch("api/leave_group.php",{
    method:"POST",
    body:new URLSearchParams({group_id:currentGroupId})
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.success){
      currentGroupId = null;
      currentGroupName = null;
      currentView = 'dms';
      document.getElementById("chatArea").style.display = 'none';
      const groupHeaderEl = document.querySelector('.group-header-actions');
      const callActionsEl = document.querySelector('.call-actions');
      if(groupHeaderEl) groupHeaderEl.style.display = 'none';
      if(callActionsEl) callActionsEl.style.display = 'none';
      document.getElementById("groupSidebar").style.display = 'none';
      localStorage.setItem('groupMembersPanelOpen', 'false');
      // If group was deleted, show message
      if(d.deleted){
        alert("Group has been deleted (you were the only member)");
      }
      showFriendsTab('dms');
    } else {
      alert(d.error);
    }
  });
}

function giveGroupOwnership(userId){
  if(!confirm("Transfer ownership to this user?")) return;
  
  fetch("api/transfer_group_ownership.php",{
    method:"POST",
    body:new URLSearchParams({group_id:currentGroupId, new_owner_id:userId})
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.success){
      alert("Ownership transferred!");
      showGroupMembersPanel();
    } else {
      alert(d.error);
    }
  });
}

// --- Select friend to chat ---
function selectFriend(id,name){
  currentFriend = id;
  //currentView = 'dms';
  currentGroupId = null;
  currentGroupName = null;
  currentServerId = null;
  // Show friend tabs
  document.querySelector('.sidebar-tabs').classList.remove('hidden');
  document.getElementById("chatTitle").innerText=name;
  document.getElementById("chatArea").style.display='flex';
  //document.getElementById("groupHeaderActions").style.display = 'none';
  localStorage.setItem('groupMembersPanelOpen', 'false');
  // Show sidebar with friend profile, hide group sidebar
  document.getElementById("chatSidebar").style.display='flex';
  document.getElementById("groupSidebar").style.display='none';
  document.getElementById("chatFriendName").innerText=name;
  // Generate avatar with first letter
  let initial = name ? name[0].toUpperCase() : '?';
  document.getElementById("chatFriendPfp").src = "https://ui-avatars.com/api/?name=" + encodeURIComponent(initial) + "&background=6366f1&color=fff&size=128";
  loadMessages();
  hideGroupMembersPanel();
  // Show call buttons for friend chat - add them dynamically
  const chatTitleEl = document.getElementById("chatTitle");
  if(chatTitleEl){
    // Check if callActions already exists
    let callActionsEl = chatTitleEl.querySelector('.call-actions');
    if(!callActionsEl){
      // Create call actions span
      callActionsEl = document.createElement('span');
      callActionsEl.className = 'call-actions';
      callActionsEl.style.cssText = 'margin-left:auto;display:inline-flex;gap:8px;';
      callActionsEl.innerHTML = `
        <button onclick="startVoiceCall()" title="Voice Call" style="padding:4px 8px;font-size:14px;background:var(--bg-tertiary);border:none;border-radius:4px;cursor:pointer;">📞</button>
        <button onclick="startVideoCall()" title="Video Call" style="padding:4px 8px;font-size:14px;background:var(--bg-tertiary);border:none;border-radius:4px;cursor:pointer;">📹</button>
      `;
      chatTitleEl.appendChild(callActionsEl);
    } else {
      callActionsEl.style.display = 'inline-flex';
    }
    
    // Hide group header actions
    const groupHeaderEl = chatTitleEl.querySelector('.group-header-actions');
    if(groupHeaderEl) groupHeaderEl.style.display = 'none';
  }
}

// --- Block/Unfriend from chat ---
function blockFromChat(){
  if(!currentFriend || !confirm("Block this user?")) return;
  fetch("api/block_user.php",{
    method:"POST",
    body:new URLSearchParams({block_id:currentFriend})
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.success){
      currentFriend = null;
      document.getElementById("chatArea").style.display='none';
      document.getElementById("chatSidebar").style.display='none';
      showFriendsTab('dms');
    } else {
      alert(d.error);
    }
  });
}

function unfriendFromChat(){
  if(!currentFriend || !confirm("Unfriend this user?")) return;
  fetch("api/unfriend.php",{
    method:"POST",
    body:new URLSearchParams({friend_id:currentFriend})
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.success){
      // Update sidebar to show "Add Friend" button instead of Unfriend/Block
      const sidebar = document.getElementById("chatSidebar");
      sidebar.innerHTML = `
        <div class="profile-section">
          <img id="chatFriendPfp" class="profile-avatar" src="${document.getElementById('chatFriendPfp').src}">
          <span id="chatFriendName" class="profile-name">${document.getElementById('chatFriendName').innerText}</span>
          <span class="profile-status">No longer friends</span>
        </div>
      `;
      // Reload friends list
      showFriendsTab('dms');
    } else {
      alert(d.error);
    }
  });
}

// --- Load messages ---
let lastMessageCount = 0;

function loadMessages(){
  if(!currentFriend) return;
  fetch("api/get_dm_messages.php?friend_id="+currentFriend)
    .then(r=>r.json())
    .then(data=>{
      let html="";
      data.forEach(m=>{
        let initial = m.username ? m.username[0].toUpperCase() : '?';
        // Format timestamp
        let time = new Date(m.created_at);
        let timeStr = time.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        // Check if message is mine
        let isMine = m.sender_id == currentUserId;
        html+=`<div class="message" data-msg-id="${m.id}">
          <div class="message-avatar">${initial}</div>
          <div class="message-content">
            <div class="message-header">
              <span class="message-username">${m.username}</span>
              <span class="message-time">${timeStr}</span>
            </div>
            <div class="message-text">${m.message}</div>
          </div>
          <div class="msg-actions">
            <button class="react-btn" onclick="showReactionPicker(${m.id})">😀</button>
            ${isMine ? '<button class="delete-msg-btn" onclick="deleteMessage(' + m.id + ')">🗑️</button>' : ''}
          </div>
          <div class="msg-reactions" id="reactions-${m.id}"></div>
        </div>`;
      });
      document.getElementById("messages").innerHTML=html;
      // Only auto-scroll if new messages arrived or it's first load
      if(data.length > lastMessageCount || lastMessageCount === 0){
        document.getElementById("messages").scrollTop = document.getElementById("messages").scrollHeight;
      }
      lastMessageCount = data.length;
      
      // Load reactions for all messages
      if(data.length > 0){
        const msgIds = data.map(m=>m.id).join(',');
        fetch("api/get_reactions.php",{
          method:"POST",
          body:new URLSearchParams({message_ids:msgIds, message_type:'dm'})
        })
        .then(r=>r.json())
        .then(d=>{
          if(d.success && d.reactions){
            Object.keys(d.reactions).forEach(msgId=>{
              renderMessageReactions(msgId, d.reactions[msgId]);
            });
          }
        });
      }
    });
}

// --- Delete DM message ---
function deleteMessage(msgId){
  if(!confirm("Delete this message?")) return;
  fetch("api/delete_dm_message.php",{
    method:"POST",
    body:new URLSearchParams({message_id:msgId})
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.success){
      loadMessages();
    } else {
      alert(d.error);
    }
  });
}

// --- Send message (DM or Group) ---
function sendMessage(){
  let msg = document.getElementById("msg").value;
  if(!msg) return;
  
  console.log("Sending message - currentView:", currentView, "currentGroupId:", currentGroupId, "currentFriend:", currentFriend);
  
  if(currentView === 'group' && currentGroupId){
    // Send to group chat
    console.log("Sending to group:", currentGroupId, "message:", msg);
    fetch("api/send_group_message.php",{
      method:"POST",
      body:new URLSearchParams({group_id:currentGroupId, message:msg})
    })
    .then(r=>r.json())
    .then(d=>{
      console.log("Response:", d);
      if(d.success){
        document.getElementById("msg").value="";
        openGroupChat(currentGroupId, currentGroupName);
      } else {
        alert(d.error);
      }
    })
    .catch(err=>{
      console.error("Error:", err);
      alert("Error sending message");
    });
  } else if(currentFriend){
    // Send DM
    fetch("api/send_dm.php",{method:'POST', body:new URLSearchParams({friend_id:currentFriend,message:msg})})
    .then(()=>{
      document.getElementById("msg").value="";
      loadMessages();
      // Stop typing indicator when message is sent
      setTyping(false);
    });
  }
}

// --- Typing indicator ---
function handleTyping(){
  if(!currentFriend) return;
  if(!isTyping){
    isTyping = true;
    setTyping(true);
  }
  // Clear existing timeout and set new one
  if(typingTimeout) clearTimeout(typingTimeout);
  typingTimeout = setTimeout(()=>{
    isTyping = false;
    setTyping(false);
  }, 2000);
}

function setTyping(typing){
  if(!currentFriend) return;
  fetch("api/typing.php",{
    method:"POST",
    body:new URLSearchParams({target_id:currentFriend, typing:typing})
  });
}

function checkTyping(){
  if(!currentFriend) return;
  fetch("api/get_typing.php?friend_id="+currentFriend)
    .then(r=>r.json())
    .then(data=>{
      const indicator = document.getElementById("typingIndicator");
      if(data.typing && data.username){
        indicator.innerText = data.username + " is typing...";
      } else {
        indicator.innerText = "";
      }
    });
}

// --- Load user info ---
function loadUserBar(){
  // Debug session first
  fetch("api/debug_session.php")
    .then(r=>r.json())
    .then(s=>{
      console.log("Session debug:", s);
    });
  
  fetch("api/get_me.php")
    .then(r=>r.json())
    .then(u=>{
      console.log("User data:", u);
      if(u.username){
        document.getElementById("usernameDisplay").innerText = u.username;
        document.getElementById("userPfp").src = u.pfp;
      } else if(u.error){
        console.error("Error loading user:", u.error);
      }
    })
    .catch(err=>{
      console.error("Failed to load user:", err);
    });
}

// --- Load servers ---
function loadServers(){
  fetch("api/get_servers.php")
    .then(r=>r.json())
    .then(servers=>{
      // TODO: Servers
      /*let html = "";
      servers.forEach(s=>{
        let initial = s.name[0] ? s.name[0].toUpperCase() : '?';
        html += `<div class="server" onclick="selectServer(${s.id}, '${s.name.join('')}')">${initial}</div>`;
      });
      // Add "create server" button
      html += `<div class="server add" onclick="createServer()">+</div>`;
      document.getElementById("servers").innerHTML = html;*/
    });
}

function selectServer(serverId, serverName){
  currentServerId = serverId;
  currentView = 'server';
  currentGroupId = null;
  currentGroupName = null;
  currentFriend = null;
  // Hide friend tabs, show server channels
  document.querySelector('.sidebar-tabs').classList.add('hidden');
  // Show server header bar
  document.getElementById("serverHeaderBar").style.display = 'flex';
  document.getElementById("serverHeaderName").innerText = serverName;
  document.getElementById("chatTitle").innerHTML = '<button onclick="backToDMs()" style="margin-right:10px;">⬅️</button> ' + serverName + ' <button onclick="openServerSettings(' + serverId + ')" style="float:right; margin-right:10px; font-size:12px;">⚙️</button>';
  document.getElementById("chatArea").style.display = 'flex';
  document.getElementById("messages").innerHTML = "<i>Select a channel to chat...</i>";
  // Hide sidebars
  document.getElementById("chatSidebar").style.display = 'none';
  document.getElementById("groupSidebar").style.display = 'none';
  // Load channels for this server
  loadChannels(serverId);
}

function backToDMs(){
  currentView = 'dms';
  currentServerId = null;
  currentFriend = null;
  currentGroupId = null;
  currentGroupName = null;
  // Show friend tabs
  document.querySelector('.sidebar-tabs').classList.remove('hidden');
  // Hide server header bar
  document.getElementById("serverHeaderBar").style.display = 'none';
  // Reset chat area
  document.getElementById("chatArea").style.display = 'none';
  const groupHeaderEl = document.querySelector('.group-header-actions');
  const callActionsEl = document.querySelector('.call-actions');
  if(groupHeaderEl) groupHeaderEl.style.display = 'none';
  if(callActionsEl) callActionsEl.style.display = 'none';
  localStorage.setItem('groupMembersPanelOpen', 'false');
  // Hide sidebars
  document.getElementById("chatSidebar").style.display = 'none';
  document.getElementById("groupSidebar").style.display = 'none';
  // Close server settings panel if open
  closeServerSettings();
  // Reload friends
  showFriendsTab('dms');
}

function loadChannels(serverId){
  fetch("api/get_channels.php?server_id="+serverId)
    .then(r=>r.json())
    .then(channels=>{
      let html = "<div class='server-section-title'>Channels</div>";
      channels.forEach(c=>{
        html += `<div class="channel" onclick="selectChannel(${c.id}, '${c.name}')"># ${c.name}</div>`;
      });
      document.getElementById("channelList").innerHTML = html;
    });
}

function selectChannel(channelId, channelName){
  document.getElementById("chatTitle").innerText = "# " + channelName;
  loadServerMessages(channelId);
}

function loadServerMessages(channelId){
  fetch("api/get_messages.php?channel_id="+channelId)
    .then(r=>r.json())
    .then(messages=>{
      let html = "";
      messages.forEach(m=>{
        let initial = m.username ? m.username[0].toUpperCase() : '?';
        let time = new Date(m.created_at);
        let timeStr = time.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        html += `<div class="message">
          <div class="message-avatar">${initial}</div>
          <div class="message-content">
            <div class="message-header">
              <span class="message-username">${m.username}</span>
              <span class="message-time">${timeStr}</span>
            </div>
            <div class="message-text">${m.message}</div>
          </div>
        </div>`;
      });
      document.getElementById("messages").innerHTML = html;
      document.getElementById("messages").scrollTop = document.getElementById("messages").scrollHeight;
    });
}

function createServer(){
  let name = prompt("Enter server name:");
  if(!name) return;
  fetch("api/create_server.php",{
    method:"POST",
    body:new URLSearchParams({name})
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.success){
      loadServers();
      alert("Server created!");
    } else {
      alert(d.error || "Failed to create server");
    }
  });
}

// --- Server settings (members & roles) ---
function openServerSettings(serverId){
  currentServerId = serverId;
  document.getElementById("serverSettings").classList.add("open");
  loadServerMembers(serverId);
  loadServerRoles(serverId);
}

function closeServerSettings(){
  document.getElementById("serverSettings").classList.remove("open");
  currentServerId = null;
}

function loadServerMembers(serverId){
  fetch("api/get_server_members.php?server_id="+serverId)
    .then(r=>r.json())
    .then(members=>{
      let html = "<h4>Members</h4>";
      members.forEach(m=>{
        html += `<div class="member-item">
          <span>${m.username}</span>
          <div id="memberRoles${m.id}"></div>
        </div>`;
        // Load their roles
        loadMemberRoles(m.id, serverId);
      });
      document.getElementById("serverMembersList").innerHTML = html;
    });
}

function loadMemberRoles(userId, serverId){
  fetch("api/get_user_roles.php?user_id="+userId+"&server_id="+serverId)
    .then(r=>r.json())
    .then(roles=>{
      let html = "";
      roles.forEach(r=>{
        html += `<span class="role-badge" style="background:${r.color}">${r.name}</span>`;
      });
      const el = document.getElementById("memberRoles"+userId);
      if(el) el.innerHTML = html;
    });
}

function loadServerRoles(serverId){
  fetch("api/get_roles.php?server_id="+serverId)
    .then(r=>r.json())
    .then(roles=>{
      let html = "";
      roles.forEach(r=>{
        html += `<div class="role-item" style="margin-bottom:8px;">
          <span class="role-badge" style="background:${r.color}">${r.name}</span>
        </div>`;
      });
      document.getElementById("serverRolesList").innerHTML = html || "<i>No roles yet</i>";
    });
}

function showCreateRoleForm(){
  document.getElementById("createRoleForm").style.display = "block";
}

function showInviteForm(){
  document.getElementById("inviteForm").style.display = "block";
}

function inviteMember(){
  const username = document.getElementById("inviteUsername").value;
  if(!username || !currentServerId) return;
  fetch("api/invite_member.php",{
    method:"POST",
    body:new URLSearchParams({username, server_id:currentServerId})
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.success){
      document.getElementById("inviteUsername").value = "";
      document.getElementById("inviteForm").style.display = "none";
      loadServerMembers(currentServerId);
      alert("Member invited!");
    } else {
      alert(d.error);
    }
  });
}

function createRole(){
  const name = document.getElementById("newRoleName").value;
  const color = document.getElementById("newRoleColor").value;
  if(!name || !currentServerId) return;
  fetch("api/create_role.php",{
    method:"POST",
    body:new URLSearchParams({name, color, server_id:currentServerId})
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.success){
      document.getElementById("newRoleName").value = "";
      document.getElementById("createRoleForm").style.display = "none";
      loadServerRoles(currentServerId);
    } else {
      alert(d.error);
    }
  });
}

// --- Auto-refresh messages & lists ---
let lastPendingCount = 0;

setInterval(()=>{
  if(currentView === 'dms'){
    if(currentFriend) {
      loadMessages();
      checkTyping();
    }
    showFriendsTab(currentTab);
    checkForNewFriendRequests();
  } else if(currentView === 'group' && currentGroupId){
    // Smart refresh group messages only
    fetch("api/get_group_messages.php",{
      method:"POST",
      body:new URLSearchParams({group_id:currentGroupId})
    })
    .then(r=>r.json())
    .then(d=>{
      if(d.success){
        renderGroupMessages(d.messages);
        renderGroupMembers(d.members);
      }
    });
  } else if(currentView === 'server' && currentServerId){
    // Refresh server channels
    loadChannels(currentServerId);
  }
},2000);

// --- Check for new friend requests (show popup) ---
function checkForNewFriendRequests(){
  fetch("api/get_friends.php")
    .then(r=>r.json())
    .then(data=>{
      const popup = document.getElementById("friendRequestPopup");
      if(data.pending && data.pending.length > lastPendingCount && lastPendingCount > 0){
        // New request came in
        const newRequest = data.pending[0];
        document.getElementById("popupUsername").innerText = newRequest.username + " wants to be friends!";
        popupPendingId = newRequest.id;
        popup.style.display = "flex";
      }
      lastPendingCount = data.pending ? data.pending.length : 0;
    });
}

let popupPendingId = null;

function acceptFriendFromPopup(){
  if(popupPendingId){
    acceptFriend(popupPendingId);
    document.getElementById("friendRequestPopup").style.display = "none";
    popupPendingId = null;
  }
}

function declineFriendFromPopup(){
  if(popupPendingId){
    declineFriend(popupPendingId);
    document.getElementById("friendRequestPopup").style.display = "none";
    popupPendingId = null;
  }
}

loadUserBar();
showFriendsTab('dms');
loadServers();

// --- Voice Call Functions ---
let inCall = false;
let callType = null; // 'voice' or 'video'
let callPeer = null; // friend or group id
let localStream = null;
let callId = null;

function startVoiceCall(){
  if(!currentFriend) return;
  if(inCall){
    alert("You're already in a call!");
    return;
  }
  
  callId = 'call_' + Date.now();
  callType = 'voice';
  callPeer = currentFriend;
  
  // Request local media
  navigator.mediaDevices.getUserMedia({audio: true, video: false})
    .then(stream => {
      localStream = stream;
      inCall = true;
      
      // Show call UI
      showCallUI(callId, currentFriend, 'voice');
      
      // Notify API
      fetch("api/call.php",{
        method:"POST",
        body:new URLSearchParams({
          call_id: callId,
          target_id: currentFriend,
          call_type: 'voice',
          action: 'initiate'
        })
      });
      
      // Start polling for call status
      pollCallStatus(callId);
    })
    .catch(err => {
      console.error("Error accessing media devices:", err);
      alert("Could not access microphone. Please check permissions.");
    });
}

function startVideoCall(){
  if(!currentFriend) return;
  if(inCall){
    alert("You're already in a call!");
    return;
  }
  
  callId = 'call_' + Date.now();
  callType = 'video';
  callPeer = currentFriend;
  
  // Request local media (audio + video)
  navigator.mediaDevices.getUserMedia({audio: true, video: true})
    .then(stream => {
      localStream = stream;
      inCall = true;
      
      // Show call UI
      showCallUI(callId, currentFriend, 'video');
      
      // Notify API
      fetch("api/call.php",{
        method:"POST",
        body:new URLSearchParams({
          call_id: callId,
          target_id: currentFriend,
          call_type: 'video',
          action: 'initiate'
        })
      });
      
      // Start polling for call status
      pollCallStatus(callId);
    })
    .catch(err => {
      console.error("Error accessing media devices:", err);
      alert("Could not access camera/microphone. Please check permissions.");
    });
}

function startGroupVoiceCall(){
  if(!currentGroupId) return;
  if(inCall){
    alert("You're already in a call!");
    return;
  }
  
  callId = 'call_' + Date.now();
  callType = 'voice';
  callPeer = currentGroupId;
  
  // Request local media
  navigator.mediaDevices.getUserMedia({audio: true, video: false})
    .then(stream => {
      localStream = stream;
      inCall = true;
      
      // Show call UI for group
      showCallUI(callId, currentGroupId, 'voice', true);
      
      // Group calls would need different handling (WebSocket for multiple participants)
      alert("Group call started! (Note: Multi-participant WebRTC requires WebSocket server)");
    })
    .catch(err => {
      console.error("Error accessing media devices:", err);
      alert("Could not access microphone. Please check permissions.");
    });
}

function showCallUI(callId, targetId, type, isGroup = false){
  // Create call overlay
  const existingCallUI = document.getElementById('callUI');
  if(existingCallUI) existingCallUI.remove();
  
  const callUI = document.createElement('div');
  callUI.id = 'callUI';
  callUI.style.cssText = 'position:fixed;bottom:20px;right:20px;background:#2a2a2a;padding:20px;border-radius:10px;z-index:1000;box-shadow:0 4px 20px rgba(0,0,0,0.5);min-width:250px;';
  
  const callTypeLabel = type === 'video' ? 'Video Call' : 'Voice Call';
  const targetLabel = isGroup ? 'Group Chat' : 'Friend';
  
  callUI.innerHTML = `
    <div style="text-align:center;margin-bottom:15px;">
      <div style="font-size:40px;">${type === 'video' ? '📹' : '📞'}</div>
      <div style="font-weight:bold;margin-top:10px;">${callTypeLabel}</div>
      <div style="color:#888;font-size:12px;">${targetLabel}</div>
      <div id="callStatus" style="color:#f1c40f;margin-top:5px;">Connecting...</div>
    </div>
    <div style="display:flex;justify-content:center;gap:10px;">
      <button onclick="endCall()" style="background:#e74c3c;color:white;border:none;padding:10px 20px;border-radius:5px;cursor:pointer;">End Call</button>
    </div>
    <div id="localVideoContainer" style="margin-top:15px;display:${type === 'video' ? 'block' : 'none'};">
      <video id="localVideo" autoplay muted style="width:100%;border-radius:5px;transform:scaleX(-1);"></video>
    </div>
  `;
  
  document.body.appendChild(callUI);
  
  // Show local video preview
  if(localStream){
    const videoEl = document.getElementById('localVideo');
    if(videoEl){
      videoEl.srcObject = localStream;
    }
  }
  
  // Update call buttons in header
  if(isGroup){
    const groupHeaderEl = document.querySelector('.group-header-actions');
    if(groupHeaderEl) groupHeaderEl.innerHTML = '<button onclick="endCall()" title="End Call" style="padding:4px 8px;font-size:14px;background:#e74c3c;">📴 End</button> <button onclick="showGroupMembersPanel()" style="padding:4px 8px;font-size:12px;">Members</button> <button onclick="showGroupInviteModal()" style="padding:4px 8px;font-size:12px;">+ Invite</button> <button onclick="leaveCurrentGroup()" style="padding:4px 8px;font-size:12px;background:#e74c3c;">Leave</button> <button onclick="showRenameGroupModal()" style="padding:4px 8px;font-size:12px;">✏️ Rename</button>';
  } else {
    const callActionsEl = document.querySelector('.call-actions');
    if(callActionsEl) callActionsEl.innerHTML = '<button onclick="endCall()" title="End Call" style="padding:4px 8px;font-size:14px;background:#e74c3c;">📴 End</button>';
  }
}

function pollCallStatus(callId){
  // Poll every 2 seconds to check if call was answered/rejected
  const pollInterval = setInterval(() => {
    if(!inCall || !callId){
      clearInterval(pollInterval);
      return;
    }
    
    fetch("api/get_call_status.php?call_id=" + callId)
      .then(r => {
        if(!r.ok) throw new Error('Network response was not ok');
        return r.json();
      })
      .then(d => {
        if(d.success && d.call){
          const statusEl = document.getElementById('callStatus');
          if(statusEl){
            if(d.call.status === 'active'){
              statusEl.innerText = 'Connected!';
              statusEl.style.color = '#2ecc71';
            } else if(d.call.status === 'rejected'){
              statusEl.innerText = 'Call rejected';
              statusEl.style.color = '#e74c3c';
              setTimeout(() => endCall(), 1500);
            } else if(d.call.status === 'ended'){
              statusEl.innerText = 'Call ended';
              statusEl.style.color = '#888';
              setTimeout(() => endCall(), 1500);
            }
          }
        }
      })
      .catch(err => {
        console.log("Call status poll error (call may have ended):", err.message);
        // Don't stop polling on error, just log it
      });
  }, 2000);
  
  // Store interval to clear on endCall
  window.callPollInterval = pollInterval;
}

function endCall(){
  if(!inCall && !callId) return;
  
  // Stop local stream
  if(localStream){
    localStream.getTracks().forEach(track => track.stop());
    localStream = null;
  }
  
  // Clear poll interval
  if(window.callPollInterval){
    clearInterval(window.callPollInterval);
    window.callPollInterval = null;
  }
  
  // Notify API
  if(callId){
    fetch("api/call.php",{
      method:"POST",
      body:new URLSearchParams({
        call_id: callId,
        target_id: callPeer,
        action: 'end'
      })
    });
  }
  
  // Reset state
  inCall = false;
  callType = null;
  callPeer = null;
  callId = null;
  
  // Remove call UI
  const callUI = document.getElementById('callUI');
  if(callUI) callUI.remove();
  
  // Restore header buttons
  if(currentFriend){
    const callActionsEl = document.querySelector('.call-actions');
    if(callActionsEl){
      callActionsEl.style.display = "inline-flex";
      callActionsEl.innerHTML = '<button onclick="startVoiceCall()" title="Voice Call" style="padding:4px 8px;font-size:14px;">📞</button> <button onclick="startVideoCall()" title="Video Call" style="padding:4px 8px;font-size:14px;">📹</button>';
    }
  } else if(currentGroupId){
    const groupHeaderEl = document.querySelector('.group-header-actions');
    if(groupHeaderEl) groupHeaderEl.innerHTML = '<button onclick="startGroupVoiceCall()" title="Voice Call" style="padding:4px 8px;font-size:14px;">📞</button> <button onclick="showGroupMembersPanel()" style="padding:4px 8px;font-size:12px;">Members</button> <button onclick="showGroupInviteModal()" style="padding:4px 8px;font-size:12px;">+ Invite</button> <button onclick="leaveCurrentGroup()" style="padding:4px 8px;font-size:12px;background:#e74c3c;">Leave</button> <button onclick="showRenameGroupModal()" style="padding:4px 8px;font-size:12px;">✏️ Rename</button>';
  }
}
</script>

</body>
</html>