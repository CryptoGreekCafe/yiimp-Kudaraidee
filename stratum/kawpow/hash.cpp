#include "stratum.h"
#include "include/ethash/progpow.hpp"
#include "utilstrencodings.h // Added for uint256
#include <stdint.h>
#include <utilstrencodings.h>
#include <ethash/ethash.hpp>


std::vector<std::pair<int, int>> coin_contexts;

void update_epoch(const int& coinid, int& height)
{
    for (size_t i = 0; i < coin_contexts.size(); i++) {
        if (coin_contexts[i].first == coinid) {
            if (coin_contexts[i].second == height) {
                return;
            }

            const auto epoch_number = ethash::find_epoch_number(uint256S(height));
            if (ethash::find_epoch_number(*ethash::get_global_epoch_context(epoch_number)) != epoch_number) {
                ethash::set_global_epoch_context(epoch_number);
            }

            coin_contexts[i].second = height;
            return;
        }
    }

    const auto epoch_number = ethash::find_epoch_number(uint256S(height));
    coin_contexts.emplace_back(coinid, height);
}

std::pair<int, int>* get_coin_context(const int& coinid)
{
    for (size_t i = 0; i < coin_contexts.size(); i++) {
        if (coin_contexts[i].first == coinid) {
            return &coin_contexts[i];
        }
    }

    return nullptr;
}

uint32_t kawpow_fullhash(uint256S& header_hash, uint64_t& header_nonce, uint256S& mix_hash, int& coinid)
{
    std::pair<int, int>* context = get_coin_context(coinid);
    if (!context) {
        return badhash;
    }

    const auto hash = to_hash256(header_hash.ToString());
    const auto result = kawpow::hash(*ethash::get_global_epoch_context(context->second), context->second, hash, header_nonce);
    mix_hash = uint256S(to_hex(result.mix_hash));
    uint256S result_hash = uint256S(to_hex(result.final_hash));

    return result_hash;
}

uint32_t kawpow_hash(std::string& header_hash, std::string& header_nonce, std::string& mix_real, int& coinid)
{
    uint256S mix_hash;

    uint256S header = uint256S(header_hash);
    uint64_t nonce = strtoull(header_nonce.c_str(), NULL, 16);
    uint32_t result_hash = kawpow_fullhash(header, nonce, mix_hash, coinid);

    mix_real = mix_hash.ToString();
    return result_hash;
}
