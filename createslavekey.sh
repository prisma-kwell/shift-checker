#kwell slave generator v1.0
#!/usr/bin/ bash

ROOT_UID=0
SUCCESS=0
E_USEREXISTS=70

sudo apt install whois
#test, if both argument are there

username="slave"
echo "enter your own unique password"
read -p "Password: " pass
pass=$pass

    # Check if user already exists.
    grep -q "$username" /etc/passwd
    if [ $? -eq $SUCCESS ] 
    then    
    echo "User $username does already exist."
    
    fi  


    useradd -p `mkpasswd "$pass"` -d /home/"$username" -m -g users -s /bin/bash "$username"

    echo "the account is setup"
mkdir /home/slave/.ssh

ssh-keygen -t rsa -b 4096 -C "shift-manager@1.com" -f "/home/slave/.ssh/id_rsa" -P "" 
cp /home/slave/.ssh/id_rsa ~/rsa.pvt
cp /home/slave/.ssh/id_rsa.pub /home/slave/.ssh/authorized_keys
chown slave: /home/slave/.ssh/authorized_keys
echo "created user slave (non root), generated key, moved to your home folder its called rsa.pub, move to master"






