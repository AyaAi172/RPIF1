# Inspired by https://www.pibits.net/code/raspberry-pi-bh1750-light-sensor.php

ONE_TIME_HIGH_RES_MODE = 0X20

class BH1750:
    def __init__(self, bus, address):
        self.bus = bus
        self.address = address

    def __toNumber(self, data):
        # Convert 2 bytes of data into a decimal number
        return ((data[1] + (256 * data[0])) / 1.2)

    def read_light(self):
        data = self.bus.read_i2c_block_data(self.address, ONE_TIME_HIGH_RES_MODE, 2)
        return self.__toNumber(data)
