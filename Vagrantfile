module LocalCommand
  class Config < Vagrant.plugin("2", :config)
    attr_accessor :command
  end

  class Plugin < Vagrant.plugin("2")
    name "local_shell"

    config(:local_shell, :provisioner) do
      Config
    end

    provisioner(:local_shell) do
      Provisioner
    end
  end

  class Provisioner < Vagrant.plugin("2", :provisioner)
    def provision
      result = system "#{config.command}"
    end
  end
end

Vagrant.configure("2") do |config|
  config.vm.box = "eugenmayer/opnsense"

  config.ssh.sudo_command = "%c"
  config.ssh.shell = "/bin/sh"
  config.ssh.password = "opnsense"
  config.ssh.username = "root"
  config.ssh.port = "10022"
  # we need to use rsync, no vbox drivers for bsd
  config.vm.synced_folder ".", "/vagrant", disabled: true

  config.vm.define 'opnsense', autostart: false do |test|
    test.vm.synced_folder "./", "/root/plugin", type: "rsync",
      rsync__chown: false,
      rsync__exclude: "./plugins/.git/",
      rsync__rsync_path: "/usr/local/bin/rsync"
    test.vm.provider 'virtualbox' do |vb|
      vb.customize ['modifyvm',:id, '--nic1', 'intnet', '--nic2', 'nat'] # swap the networks around
      vb.customize ['modifyvm', :id, '--natpf2', "ssh,tcp,127.0.0.1,10022,,22" ] #port forward
      vb.customize ['modifyvm', :id, '--natpf2', "https,tcp,127.0.0.1,10443,,443" ] #port forward
      vb.customize ['modifyvm', :id, '--natpf2', "openvpn,tcp,127.0.0.1,11194,,1194" ] # openvpn
      #vb.customize ['modifyvm', :id, '--natpf1', "https,tcp,127.0.0.1,1443,,443" ] #port forward
    end

    # install dev tools
    test.vm.provision "shell",
      inline: "pkg update && pkg install -y vim-lite joe nano gnu-watch git tmux screen",
      run: "once"

    # install dev tools
    test.vm.provision "shell",
      inline: "pkg update && pkg install -y vim-lite joe nano gnu-watch git tmux screen",
      run: "once"

    # replace the public ssh key for the root user with the one vagrant deployed for comms before we restart - or we lock vagrant out
    test.vm.provision "inject-pubkey-into-config", type: "local_shell", command: "export PUB=$(ssh-keygen -f .vagrant/machines/opnsense/virtualbox/private_key -y | base64) && xmlstarlet ed --inplace -u '/opnsense/system/user/authorizedkeys' -v \"$PUB\" config-radius-openvpn.xml"
    # apply our configuration so we have a configured radius with users and clients and an active openvpn server
    test.vm.provision "file", source: "./config-radius-openvpn.xml", destination: "/conf/config.xml"
    test.vm.provision "shell",
      inline: "echo 'rebooting to apply config' && reboot"

    test.vm.provision "sleep-for-reboot", type: "local_shell", command: "echo 'waiting for the reboot' && sleep 50"
    # this will register our local core from source and let opnsense run from that
    # test.vm.provision "shell",
    #   inline: "cd /root/core && make mount",
    #   run: "once"

    # # that will install our local freeradius plugin version into opnsense
    # test.vm.provision "shell",
    #   inline: "cd /root/plugins/net/freeradius && make package && pkg add work/pkg/*.txz"

    # that will install our local freeradius plugin version into opnsense
    test.vm.provision "shell",
      inline: "cd /root/plugin/net/unbound && make package && pkg add work/pkg/*.txz"
  end
end
