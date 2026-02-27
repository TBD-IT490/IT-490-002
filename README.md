# IT-490-002

Ubuntu iso : https://ubuntu.com/download/server/thank-you?version=24.04.4&architecture=amd64&lts=true
When seting up your vm use this iso^^ double check its this one.

Provide 6144 MB exactly with 4 cores.
With 30GB of of sata storage, not 25.

When in the installtion proccess don't change anything but adding 3rd party drivers and making sure openssh server is installed. 
your name and user and password should all be it490, the server name should be what the server will handle so web, dmz, database or broker.
When the proccess is complete reboot the VM, press enter and wait.

Try logging in, if all is good the only thing you should do now is run these commands and nothing else.
```bash
ssh-keygen -t ed25519 -C "your_email@example.com"
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/id_ed25519
cat ~/.ssh/id_ed25519.pub
```
After this copy the public key into github and name it i490-vm_name and send to discord for us to use. Now you can clone the repo
```bash
git clone git@github.com:TBD-IT490/IT-490-002.git
```
Once cloned, cd into the repo and run the install.sh script.
